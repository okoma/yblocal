<?php

namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Models\Subscription;
use App\Models\Wallet;
use App\Models\AdCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Payment Controller
 * 
 * Handles ALL payment webhooks and callbacks for all gateways
 * Routes differentiate between gateways
 */
class PaymentController extends Controller
{
    // ==========================================
    // WEBHOOKS (Server-to-Server)
    // ==========================================
    
    /**
     * Handle Paystack webhook
     */
    public function paystackWebhook(Request $request)
    {
        $gateway = $this->getGateway('paystack');
        if (!$gateway) {
            return response()->json(['error' => 'Gateway not configured'], 400);
        }

        // Verify signature
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();
        
        if (!hash_equals(hash_hmac('sha512', $payload, $gateway->secret_key), $signature ?? '')) {
            Log::error('Paystack webhook: Invalid signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = json_decode($payload, true);
        if (!$event || !isset($event['event'])) {
            return response()->json(['error' => 'Invalid event'], 400);
        }

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
        $gateway = $this->getGateway('flutterwave');
        if (!$gateway) {
            return response()->json(['error' => 'Gateway not configured'], 400);
        }

        // Verify signature
        $signature = $request->header('verif-hash');
        $payload = $request->all();
        
        $computedHash = hash_hmac('sha256', json_encode($payload), $gateway->secret_key);
        if (!hash_equals($computedHash, $signature ?? '')) {
            Log::error('Flutterwave webhook: Invalid signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];

        if (!$event) {
            return response()->json(['error' => 'Invalid event'], 400);
        }

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
        
        if (!$reference) {
            return $this->redirectWithError('Invalid payment reference.', null);
        }

        $transaction = $this->findTransaction($reference, 'paystack');
        if (!$transaction) {
            return $this->redirectWithError('Transaction not found.', null);
        }

        $gateway = $this->getGateway('paystack');
        if (!$gateway) {
            return $this->redirectWithError('Payment gateway not configured.', $transaction);
        }

        // Verify with Paystack API
        $verified = $this->verifyPaystack($reference, $gateway->secret_key);

        if ($verified && $verified['status'] === 'success') {
            if ($transaction->status === 'pending') {
                $transaction->update([
                    'payment_gateway_ref' => $reference,
                    'gateway_response' => $verified,
                ]);
                $transaction->markAsPaid();
                $this->activatePayable($transaction);
            }
            return $this->redirectWithSuccess('Payment successful! Your purchase has been activated.', $transaction);
        }

        // Failed
        if ($transaction->status === 'pending') {
            $transaction->update(['gateway_response' => $verified ?? ['error' => 'Verification failed']]);
            $transaction->markAsFailed();
        }
        return $this->redirectWithError('Payment failed. Please try again.', $transaction);
    }

    /**
     * Handle Flutterwave callback (user redirect after payment)
     */
    public function flutterwaveCallback(Request $request)
    {
        $txRef = $request->query('tx_ref') ?? $request->query('transaction_id');
        $status = $request->query('status');
        
        if (!$txRef) {
            return $this->redirectWithError('Invalid payment reference.', null);
        }

        $transaction = $this->findTransaction($txRef, 'flutterwave');
        if (!$transaction) {
            return $this->redirectWithError('Transaction not found.', null);
        }

        $gateway = $this->getGateway('flutterwave');
        if (!$gateway) {
            return $this->redirectWithError('Payment gateway not configured.', $transaction);
        }

        // Verify with Flutterwave API
        $verified = $this->verifyFlutterwave($txRef, $gateway->secret_key);

        if ($verified && $verified['status'] === 'successful') {
            if ($transaction->status === 'pending') {
                $transaction->update([
                    'payment_gateway_ref' => $txRef,
                    'gateway_response' => $verified,
                ]);
                $transaction->markAsPaid();
                $this->activatePayable($transaction);
            }
            return $this->redirectWithSuccess('Payment successful! Your purchase has been activated.', $transaction);
        }

        // Failed
        if ($transaction->status === 'pending') {
            $transaction->update(['gateway_response' => $verified ?? ['error' => 'Verification failed', 'status' => $status]]);
            $transaction->markAsFailed();
        }
        return $this->redirectWithError('Payment failed. Please try again.', $transaction);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================
    
    /**
     * Get payment gateway
     */
    protected function getGateway(string $slug): ?PaymentGateway
    {
        return PaymentGateway::where('slug', $slug)
            ->where('is_enabled', true)
            ->first();
    }

    /**
     * Find transaction by reference
     */
    protected function findTransaction(string $reference, string $method): ?Transaction
    {
        return Transaction::where(function($query) use ($reference) {
                $query->where('transaction_ref', $reference)
                      ->orWhere('payment_gateway_ref', $reference);
            })
            ->where('payment_method', $method)
            ->first();
    }

    /**
     * Handle successful payment (webhook)
     */
    protected function handleSuccess(array $data, string $gateway, ?string $reference): void
    {
        if (!$reference) {
            Log::error("$gateway webhook: Missing reference");
            return;
        }

        $transaction = $this->findTransaction($reference, $gateway);
        if (!$transaction || $transaction->status !== 'pending') {
            Log::warning("$gateway webhook: Transaction not found or already processed", ['reference' => $reference]);
            return;
        }

        $transaction->update([
            'payment_gateway_ref' => $reference,
            'gateway_response' => $data,
        ]);
        $transaction->markAsPaid();
        $this->activatePayable($transaction);

        Log::info("$gateway webhook: Payment processed", [
            'reference' => $reference,
            'transaction_id' => $transaction->id,
        ]);
    }

    /**
     * Handle failed payment (webhook)
     */
    protected function handleFailure(array $data, string $gateway, ?string $reference): void
    {
        if (!$reference) {
            return;
        }

        $transaction = $this->findTransaction($reference, $gateway);
        if ($transaction && $transaction->status === 'pending') {
            $transaction->update([
                'payment_gateway_ref' => $reference,
                'gateway_response' => $data,
            ]);
            $transaction->markAsFailed();

            Log::info("$gateway webhook: Payment failed", [
                'reference' => $reference,
                'transaction_id' => $transaction->id,
            ]);
        }
    }

    /**
     * Activate payable (Subscription, AdCampaign, Wallet)
     */
    protected function activatePayable(Transaction $transaction): void
    {
        $payable = $transaction->transactionable;
        
        if (!$payable) {
            Log::warning('Transaction has no payable', ['transaction_id' => $transaction->id]);
            return;
        }

        match (true) {
            $payable instanceof Subscription => $this->activateSubscription($payable),
            $payable instanceof AdCampaign => $payable->update(['is_paid' => true, 'is_active' => true]),
            $payable instanceof Wallet => $payable->deposit($transaction->amount, 'Payment gateway funding', $transaction),
            default => Log::warning('Unknown payable type', ['type' => get_class($payable)]),
        };

        Log::info('Payable activated', [
            'transaction_id' => $transaction->id,
            'type' => get_class($payable),
            'payable_id' => $payable->id,
        ]);
    }

    /**
     * Activate subscription and grant premium if verified
     */
    protected function activateSubscription(Subscription $subscription): void
    {
        // Activate the subscription
        $subscription->update(['status' => 'active']);

        // Get the business
        $business = $subscription->business;

        if ($business) {
            // Grant premium ONLY if business is verified
            if ($business->is_verified) {
                $business->update([
                    'is_premium' => true,
                    'premium_until' => $subscription->ends_at,
                ]);

                Log::info('Premium granted', [
                    'subscription_id' => $subscription->id,
                    'business_id' => $business->id,
                    'reason' => 'Verified + Active Subscription',
                ]);
            } else {
                Log::info('Subscription active but no premium (not verified)', [
                    'subscription_id' => $subscription->id,
                    'business_id' => $business->id,
                ]);
            }
        }
    }

    /**
     * Verify Paystack transaction
     */
    protected function verifyPaystack(string $reference, string $secretKey): ?array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['Authorization' => 'Bearer ' . $secretKey])
                ->get("https://api.paystack.co/transaction/verify/$reference");

            if ($response->successful() && $response->json('status')) {
                return $response->json('data');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Paystack verification error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verify Flutterwave transaction
     */
    protected function verifyFlutterwave(string $txRef, string $secretKey): ?array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['Authorization' => 'Bearer ' . $secretKey])
                ->get("https://api.flutterwave.com/v3/transactions/$txRef/verify");

            if ($response->successful() && $response->json('status') === 'success') {
                return $response->json('data');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Flutterwave verification error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get redirect URL based on transaction type
     */
    protected function getRedirectUrl(?Transaction $transaction = null): string
    {
        if (!$transaction || !$transaction->transactionable_type) {
            return route('filament.business.pages.dashboard');
        }

        // Redirect to the relevant page based on what was paid for
        return match ($transaction->transactionable_type) {
            'App\Models\Subscription', \App\Models\Subscription::class => \App\Filament\Business\Resources\SubscriptionResource::getUrl('view', ['record' => $transaction->transactionable_id], panel: 'business'),
            'App\Models\AdCampaign', \App\Models\AdCampaign::class => \App\Filament\Business\Resources\AdCampaignResource::getUrl('view', ['record' => $transaction->transactionable_id], panel: 'business'),
            'App\Models\Wallet', \App\Models\Wallet::class => route('filament.business.pages.wallet-page'),
            default => route('filament.business.pages.dashboard'),
        };
    }

    /**
     * Redirect with success message
     */
    protected function redirectWithSuccess(string $message, ?Transaction $transaction = null)
    {
        return redirect()->to($this->getRedirectUrl($transaction))
            ->with('success', $message);
    }

    /**
     * Redirect with error message
     */
    protected function redirectWithError(string $message, ?Transaction $transaction = null)
    {
        return redirect()->to($this->getRedirectUrl($transaction))
            ->with('error', $message);
    }

    // ==========================================
    // RECEIPT GENERATION
    // ==========================================
    
    /**
     * Download transaction receipt as PDF
     */
    public function downloadReceipt(Transaction $transaction)
    {
        // Ensure user owns this transaction
        $user = auth()->user();
        
        // Check if user owns the transaction or owns the business associated with it
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
        
        // Only allow receipt for completed transactions
        if ($transaction->status !== 'completed') {
            abort(403, 'Receipt is only available for completed transactions.');
        }
        
        // Load relationships
        $transaction->load(['user', 'transactionable', 'gateway']);
        
        // Generate PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('receipts.transaction', [
            'transaction' => $transaction,
        ]);
        
        // Generate filename
        $filename = 'receipt-' . $transaction->reference . '.pdf';
        
        // Download PDF
        return $pdf->download($filename);
    }
}
