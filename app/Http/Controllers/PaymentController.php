<?php
// ============================================



namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Services\ActivationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected ActivationService $activationService
    ) {}

    // ==========================================
    // WEBHOOKS (Server-to-Server)
    // ==========================================
    
    /**
     * Handle Paystack webhook
     */
    public function paystackWebhook(Request $request)
    {
        Log::info('Paystack webhook received', [
            'headers' => $request->headers->all(),
            'ip' => $request->ip(),
        ]);

        $gateway = $this->getGateway('paystack');
        if (!$gateway) {
            Log::error('Paystack webhook: Gateway not configured');
            return response()->json(['error' => 'Gateway not configured'], 400);
        }

        // Verify signature
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();
        
        $computedSignature = hash_hmac('sha512', $payload, $gateway->secret_key);
        
        if (!hash_equals($computedSignature, $signature ?? '')) {
            Log::error('Paystack webhook: Invalid signature', [
                'received' => $signature,
                'expected' => substr($computedSignature, 0, 10) . '...',
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = json_decode($payload, true);
        if (!$event || !isset($event['event'])) {
            Log::error('Paystack webhook: Invalid event structure');
            return response()->json(['error' => 'Invalid event'], 400);
        }

        Log::info('Paystack webhook: Event received', [
            'event' => $event['event'],
            'reference' => $event['data']['reference'] ?? null,
        ]);

        // Handle event
        match ($event['event']) {
            'charge.success' => $this->handleSuccess($event['data'], 'paystack', $event['data']['reference'] ?? null),
            'charge.failed' => $this->handleFailure($event['data'], 'paystack', $event['data']['reference'] ?? null),
            default => Log::info('Paystack webhook: Unhandled event', ['event' => $event['event']]),
        };

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Flutterwave webhook
     */
    public function flutterwaveWebhook(Request $request)
    {
        Log::info('Flutterwave webhook received', [
            'headers' => $request->headers->all(),
            'ip' => $request->ip(),
        ]);

        $gateway = $this->getGateway('flutterwave');
        if (!$gateway) {
            Log::error('Flutterwave webhook: Gateway not configured');
            return response()->json(['error' => 'Gateway not configured'], 400);
        }

        // Verify signature (Flutterwave sends their secret hash directly)
        $signature = $request->header('verif-hash');
        
        if (!$signature || !hash_equals($gateway->secret_key, $signature)) {
            Log::error('Flutterwave webhook: Invalid signature', [
                'received' => $signature,
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = $request->all();
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];

        if (!$event) {
            Log::error('Flutterwave webhook: Missing event');
            return response()->json(['error' => 'Invalid event'], 400);
        }

        Log::info('Flutterwave webhook: Event received', [
            'event' => $event,
            'tx_ref' => $data['tx_ref'] ?? $data['flw_ref'] ?? null,
        ]);

        // Handle event
        match ($event) {
            'charge.completed', 'charge.succeeded' => $this->handleSuccess($data, 'flutterwave', $data['tx_ref'] ?? $data['flw_ref'] ?? null),
            'charge.failed' => $this->handleFailure($data, 'flutterwave', $data['tx_ref'] ?? $data['flw_ref'] ?? null),
            default => Log::info('Flutterwave webhook: Unhandled event', ['event' => $event]),
        };

        return response()->json(['status' => 'success']);
    }

    // ==========================================
    // CALLBACKS (User Redirects)
    // ==========================================
    
    /**
     * Handle Paystack callback (user redirect after payment)
     */
    public function paystackCallback(Request $request)
    {
        $reference = $request->query('reference');
        
        Log::info('Paystack callback received', [
            'reference' => $reference,
            'query' => $request->query(),
        ]);
        
        if (!$reference) {
            return $this->redirectWithError('Invalid payment reference.', null);
        }

        $transaction = $this->findTransaction($reference, 'paystack');
        if (!$transaction) {
            Log::warning('Paystack callback: Transaction not found', ['reference' => $reference]);
            return $this->redirectWithError('Transaction not found.', null);
        }

        // If already processed, just redirect
        if ($transaction->status !== 'pending') {
            Log::info('Paystack callback: Transaction already processed', [
                'reference' => $reference,
                'status' => $transaction->status,
            ]);
            return $this->redirectWithSuccess('Payment already processed.', $transaction);
        }

        $gateway = $this->getGateway('paystack');
        if (!$gateway) {
            return $this->redirectWithError('Payment gateway not configured.', $transaction);
        }

        // Verify with Paystack API
        $verified = $this->verifyPaystack($reference, $gateway->secret_key);

        if ($verified && $verified['status'] === 'success') {
            $transaction->update([
                'payment_gateway_ref' => $reference,
                'gateway_response' => $verified,
            ]);
            $this->activationService->completeAndActivate($transaction);
            
            Log::info('Paystack callback: Payment successful', [
                'reference' => $reference,
                'transaction_id' => $transaction->id,
            ]);
            
            return $this->redirectWithSuccess('Payment successful! Your purchase has been activated.', $transaction);
        }

        // Failed
        Log::warning('Paystack callback: Verification failed', [
            'reference' => $reference,
            'verified_data' => $verified,
        ]);
        
        $transaction->update(['gateway_response' => $verified ?? ['error' => 'Verification failed']]);
        $transaction->markAsFailed();
        
        return $this->redirectWithError('Payment verification failed. Please contact support.', $transaction);
    }

    /**
     * Handle Flutterwave callback (user redirect after payment)
     */
    public function flutterwaveCallback(Request $request)
    {
        $txRef = $request->query('tx_ref') ?? $request->query('transaction_id');
        $status = $request->query('status');
        
        Log::info('Flutterwave callback received', [
            'tx_ref' => $txRef,
            'status' => $status,
            'query' => $request->query(),
        ]);
        
        if (!$txRef) {
            return $this->redirectWithError('Invalid payment reference.', null);
        }

        $transaction = $this->findTransaction($txRef, 'flutterwave');
        if (!$transaction) {
            Log::warning('Flutterwave callback: Transaction not found', ['tx_ref' => $txRef]);
            return $this->redirectWithError('Transaction not found.', null);
        }

        // If already processed, just redirect
        if ($transaction->status !== 'pending') {
            Log::info('Flutterwave callback: Transaction already processed', [
                'tx_ref' => $txRef,
                'status' => $transaction->status,
            ]);
            return $this->redirectWithSuccess('Payment already processed.', $transaction);
        }

        $gateway = $this->getGateway('flutterwave');
        if (!$gateway) {
            return $this->redirectWithError('Payment gateway not configured.', $transaction);
        }

        // Verify with Flutterwave API
        $verified = $this->verifyFlutterwave($txRef, $gateway->secret_key);

        if ($verified && $verified['status'] === 'successful') {
            $transaction->update([
                'payment_gateway_ref' => $txRef,
                'gateway_response' => $verified,
            ]);
            $this->activationService->completeAndActivate($transaction);
            
            Log::info('Flutterwave callback: Payment successful', [
                'tx_ref' => $txRef,
                'transaction_id' => $transaction->id,
            ]);
            
            return $this->redirectWithSuccess('Payment successful! Your purchase has been activated.', $transaction);
        }

        // Failed
        Log::warning('Flutterwave callback: Verification failed', [
            'tx_ref' => $txRef,
            'verified_data' => $verified,
        ]);
        
        $transaction->update(['gateway_response' => $verified ?? ['error' => 'Verification failed', 'status' => $status]]);
        $transaction->markAsFailed();
        
        return $this->redirectWithError('Payment verification failed. Please contact support.', $transaction);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================
    
    protected function getGateway(string $slug): ?PaymentGateway
    {
        return PaymentGateway::where('slug', $slug)
            ->where('is_enabled', true)
            ->first();
    }

    protected function findTransaction(string $reference, string $method): ?Transaction
    {
        return Transaction::where(function($query) use ($reference) {
                $query->where('transaction_ref', $reference)
                      ->orWhere('payment_gateway_ref', $reference);
            })
            ->where('payment_method', $method)
            ->first();
    }

    protected function handleSuccess(array $data, string $gateway, ?string $reference): void
    {
        if (!$reference) {
            Log::error("$gateway webhook: Missing reference in success event");
            return;
        }

        $transaction = $this->findTransaction($reference, $gateway);
        if (!$transaction) {
            Log::warning("$gateway webhook: Transaction not found", ['reference' => $reference]);
            return;
        }

        if ($transaction->status !== 'pending') {
            Log::info("$gateway webhook: Transaction already processed (idempotent)", [
                'reference' => $reference,
                'status' => $transaction->status,
            ]);
            return;
        }

        $transaction->update([
            'payment_gateway_ref' => $reference,
            'gateway_response' => $data,
        ]);
        
        $this->activationService->completeAndActivate($transaction);

        Log::info("$gateway webhook: Payment processed successfully", [
            'reference' => $reference,
            'transaction_id' => $transaction->id,
        ]);
    }

    protected function handleFailure(array $data, string $gateway, ?string $reference): void
    {
        if (!$reference) {
            Log::error("$gateway webhook: Missing reference in failure event");
            return;
        }

        $transaction = $this->findTransaction($reference, $gateway);
        if (!$transaction) {
            Log::warning("$gateway webhook: Transaction not found", ['reference' => $reference]);
            return;
        }

        if ($transaction->status !== 'pending') {
            Log::info("$gateway webhook: Transaction already processed", [
                'reference' => $reference,
                'status' => $transaction->status,
            ]);
            return;
        }

        $transaction->update([
            'payment_gateway_ref' => $reference,
            'gateway_response' => $data,
        ]);
        
        $transaction->markAsFailed();

        Log::info("$gateway webhook: Payment marked as failed", [
            'reference' => $reference,
            'transaction_id' => $transaction->id,
        ]);
    }

    protected function verifyPaystack(string $reference, string $secretKey): ?array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['Authorization' => 'Bearer ' . $secretKey])
                ->get("https://api.paystack.co/transaction/verify/$reference");

            if ($response->successful() && $response->json('status')) {
                return $response->json('data');
            }

            Log::warning('Paystack verification returned unsuccessful', [
                'reference' => $reference,
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Paystack verification error', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function verifyFlutterwave(string $txRef, string $secretKey): ?array
    {
        try {
            // Note: Flutterwave uses transaction ID, not tx_ref for verification
            $response = Http::timeout(30)
                ->withHeaders(['Authorization' => 'Bearer ' . $secretKey])
                ->get("https://api.flutterwave.com/v3/transactions/verify_by_reference", [
                    'tx_ref' => $txRef,
                ]);

            if ($response->successful() && $response->json('status') === 'success') {
                return $response->json('data');
            }

            Log::warning('Flutterwave verification returned unsuccessful', [
                'tx_ref' => $txRef,
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Flutterwave verification error', [
                'tx_ref' => $txRef,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function getRedirectUrl(?Transaction $transaction = null): string
    {
        if (!$transaction || !$transaction->transactionable_type) {
            return route('filament.business.pages.dashboard');
        }

        return match ($transaction->transactionable_type) {
            'App\Models\Subscription', \App\Models\Subscription::class => 
                \App\Filament\Business\Resources\SubscriptionResource::getUrl('view', ['record' => $transaction->transactionable_id], panel: 'business'),
            'App\Models\AdCampaign', \App\Models\AdCampaign::class => 
                \App\Filament\Business\Resources\AdCampaignResource::getUrl('view', ['record' => $transaction->transactionable_id], panel: 'business'),
            'App\Models\Wallet', \App\Models\Wallet::class => 
                route('filament.business.pages.wallet-page'),
            default => route('filament.business.pages.dashboard'),
        };
    }

    protected function redirectWithSuccess(string $message, ?Transaction $transaction = null)
    {
        return redirect()->to($this->getRedirectUrl($transaction))
            ->with('success', $message);
    }

    protected function redirectWithError(string $message, ?Transaction $transaction = null)
    {
        return redirect()->to($this->getRedirectUrl($transaction))
            ->with('error', $message);
    }

    public function downloadReceipt(Transaction $transaction)
    {
        $user = auth()->user();
        
        $isOwner = $transaction->user_id === $user->id;
        
        if ($transaction->transactionable_type === 'App\Models\Subscription') {
            $subscription = $transaction->transactionable;
            if ($subscription && $subscription->business_id) {
                $business = $subscription->business;
                $isOwner = $isOwner || ($business && $business->user_id === $user->id);
            }
        }
        
        if ($transaction->transactionable_type === 'App\Models\AdCampaign') {
            $campaign = $transaction->transactionable;
            if ($campaign && $campaign->business_id) {
                $business = $campaign->business;
                $isOwner = $isOwner || ($business && $business->user_id === $user->id);
            }
        }
        
        if (!$isOwner) {
            abort(403, 'Unauthorized access to this receipt.');
        }
        
        if ($transaction->status !== 'completed') {
            abort(403, 'Receipt is only available for completed transactions.');
        }
        
        $transaction->load(['user', 'transactionable', 'gateway']);
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('receipts.transaction', [
            'transaction' => $transaction,
        ]);
        
        $filename = 'receipt-' . $transaction->transaction_ref . '.pdf';
        
        return $pdf->download($filename);
    }
}