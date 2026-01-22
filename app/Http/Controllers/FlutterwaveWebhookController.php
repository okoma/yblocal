<?php

namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Models\Subscription;
use App\Models\Wallet;
use App\Models\AdCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FlutterwaveWebhookController extends Controller
{
    /**
     * Handle Flutterwave webhook
     */
    public function handle(Request $request)
    {
        $gateway = PaymentGateway::where('slug', 'flutterwave')
            ->where('is_enabled', true)
            ->first();

        if (!$gateway || !$gateway->secret_key) {
            Log::error('Flutterwave webhook: Gateway not configured');
            return response()->json(['error' => 'Gateway not configured'], 400);
        }

        // Verify webhook signature
        $signature = $request->header('verif-hash');
        $payload = $request->all();
        
        if (!$this->verifySignature($payload, $signature, $gateway->secret_key)) {
            Log::error('Flutterwave webhook: Invalid signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];

        if (!$event) {
            Log::error('Flutterwave webhook: Invalid event data');
            return response()->json(['error' => 'Invalid event'], 400);
        }

        // Handle different event types
        switch ($event) {
            case 'charge.completed':
            case 'charge.succeeded':
                $this->handleSuccessfulPayment($data);
                break;
            
            case 'charge.failed':
                $this->handleFailedPayment($data);
                break;
            
            default:
                Log::info('Flutterwave webhook: Unhandled event', ['event' => $event]);
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Verify Flutterwave webhook signature
     */
    protected function verifySignature(array $payload, ?string $signature, string $secret): bool
    {
        if (!$signature) {
            return false;
        }

        // Flutterwave uses a simple hash verification
        $computedHash = hash_hmac('sha256', json_encode($payload), $secret);
        
        return hash_equals($computedHash, $signature);
    }

    /**
     * Handle successful payment
     */
    protected function handleSuccessfulPayment(array $data)
    {
        $txRef = $data['tx_ref'] ?? $data['flw_ref'] ?? null;
        
        if (!$txRef) {
            Log::error('Flutterwave webhook: Missing transaction reference in successful payment');
            return;
        }

        // Find transaction by reference (check both transaction_ref and payment_gateway_ref)
        // Flutterwave sends reference in 'tx_ref' or 'flw_ref'
        $transaction = Transaction::where(function($query) use ($txRef) {
                $query->where('transaction_ref', $txRef)
                      ->orWhere('payment_gateway_ref', $txRef);
            })
            ->where('payment_method', 'flutterwave')
            ->where('status', 'pending') // Only process pending transactions
            ->first();

        if (!$transaction) {
            Log::warning('Flutterwave webhook: Transaction not found', ['reference' => $txRef]);
            return;
        }

        // Update transaction with gateway reference and response
        $transaction->update([
            'payment_gateway_ref' => $txRef,
            'gateway_response' => $data,
        ]);

        // Use the model's markAsPaid method
        $transaction->markAsPaid();

        // Handle different transaction types
        $this->handleTransactionable($transaction);

        Log::info('Flutterwave webhook: Payment processed successfully', [
            'reference' => $txRef,
            'transaction_id' => $transaction->id,
        ]);
    }

    /**
     * Handle failed payment
     */
    protected function handleFailedPayment(array $data)
    {
        $txRef = $data['tx_ref'] ?? $data['flw_ref'] ?? null;
        
        if (!$txRef) {
            return;
        }

        $transaction = Transaction::where(function($query) use ($txRef) {
                $query->where('transaction_ref', $txRef)
                      ->orWhere('payment_gateway_ref', $txRef);
            })
            ->where('payment_method', 'flutterwave')
            ->where('status', 'pending')
            ->first();

        if ($transaction) {
            // Update gateway reference if not set
            if (!$transaction->payment_gateway_ref) {
                $transaction->update(['payment_gateway_ref' => $txRef]);
            }
            
            // Use the model's markAsFailed method
            $transaction->markAsFailed();
            $transaction->update(['gateway_response' => $data]);

            Log::info('Flutterwave webhook: Payment failed', [
                'reference' => $txRef,
                'transaction_id' => $transaction->id,
            ]);
        }
    }

    /**
     * Handle different transactionable types (Subscription, Wallet, AdCampaign)
     */
    protected function handleTransactionable(Transaction $transaction)
    {
        if (!$transaction->transactionable_type || !$transaction->transactionable_id) {
            Log::warning('Flutterwave webhook: Transaction has no transactionable', [
                'transaction_id' => $transaction->id,
            ]);
            return;
        }

        $transactionableType = $transaction->transactionable_type;
        
        if ($transactionableType === Subscription::class || $transactionableType === 'App\\Models\\Subscription') {
            $this->handleSubscriptionPayment($transaction);
        } elseif ($transactionableType === Wallet::class || $transactionableType === 'App\\Models\\Wallet') {
            $this->handleWalletFunding($transaction);
        } elseif ($transactionableType === AdCampaign::class || $transactionableType === 'App\\Models\\AdCampaign') {
            $this->handleCampaignPayment($transaction);
        } else {
            Log::info('Flutterwave webhook: Unhandled transactionable type', [
                'type' => $transactionableType,
                'transaction_id' => $transaction->id,
            ]);
        }
    }

    /**
     * Handle subscription payment
     */
    protected function handleSubscriptionPayment(Transaction $transaction)
    {
        $subscription = Subscription::find($transaction->transactionable_id);
        
        if (!$subscription) {
            Log::warning('Flutterwave webhook: Subscription not found', [
                'subscription_id' => $transaction->transactionable_id,
                'transaction_id' => $transaction->id,
            ]);
            return;
        }

        $subscription->update([
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays(30), // Default 30 days, adjust as needed
        ]);

        Log::info('Flutterwave webhook: Subscription activated', [
            'subscription_id' => $subscription->id,
            'transaction_id' => $transaction->id,
        ]);
    }

    /**
     * Handle wallet funding
     */
    protected function handleWalletFunding(Transaction $transaction)
    {
        $wallet = Wallet::find($transaction->transactionable_id);
        
        if (!$wallet) {
            Log::warning('Flutterwave webhook: Wallet not found', [
                'wallet_id' => $transaction->transactionable_id,
                'transaction_id' => $transaction->id,
            ]);
            return;
        }

        // Deposit funds to wallet
        $wallet->deposit(
            $transaction->amount,
            'Wallet funding via Flutterwave',
            $transaction
        );

        Log::info('Flutterwave webhook: Wallet funded', [
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'amount' => $transaction->amount,
            'transaction_id' => $transaction->id,
        ]);
    }

    /**
     * Handle campaign payment
     */
    protected function handleCampaignPayment(Transaction $transaction)
    {
        $campaign = AdCampaign::find($transaction->transactionable_id);
        
        if (!$campaign) {
            Log::warning('Flutterwave webhook: Campaign not found', [
                'campaign_id' => $transaction->transactionable_id,
                'transaction_id' => $transaction->id,
            ]);
            return;
        }

        $campaign->update([
            'is_paid' => true,
            'is_active' => true,
            'transaction_id' => $transaction->id,
        ]);

        Log::info('Flutterwave webhook: Campaign payment processed', [
            'campaign_id' => $campaign->id,
            'transaction_id' => $transaction->id,
        ]);
    }
}
