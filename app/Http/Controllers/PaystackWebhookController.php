<?php

namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Models\Subscription;
use App\Models\Wallet;
use App\Models\AdCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    /**
     * Handle Paystack webhook
     */
    public function handle(Request $request)
    {
        $gateway = PaymentGateway::where('slug', 'paystack')
            ->where('is_enabled', true)
            ->first();

        if (!$gateway || !$gateway->secret_key) {
            Log::error('Paystack webhook: Gateway not configured');
            return response()->json(['error' => 'Gateway not configured'], 400);
        }

        // Verify webhook signature
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();
        
        if (!$this->verifySignature($payload, $signature, $gateway->secret_key)) {
            Log::error('Paystack webhook: Invalid signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = json_decode($payload, true);

        if (!$event || !isset($event['event'])) {
            Log::error('Paystack webhook: Invalid event data');
            return response()->json(['error' => 'Invalid event'], 400);
        }

        // Handle different event types
        switch ($event['event']) {
            case 'charge.success':
                $this->handleSuccessfulPayment($event['data']);
                break;
            
            case 'charge.failed':
                $this->handleFailedPayment($event['data']);
                break;
            
            default:
                Log::info('Paystack webhook: Unhandled event', ['event' => $event['event']]);
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Verify Paystack webhook signature
     */
    protected function verifySignature(string $payload, ?string $signature, string $secret): bool
    {
        if (!$signature) {
            return false;
        }

        $computedSignature = hash_hmac('sha512', $payload, $secret);
        
        return hash_equals($computedSignature, $signature);
    }

    /**
     * Handle successful payment
     */
    protected function handleSuccessfulPayment(array $data)
    {
        $reference = $data['reference'] ?? null;
        
        if (!$reference) {
            Log::error('Paystack webhook: Missing reference in successful payment');
            return;
        }

        // Find transaction by reference (check both transaction_ref and payment_gateway_ref)
        // Paystack sends the reference in the 'reference' field
        // Note: payment_method is 'paystack' regardless of which Paystack payment method (card, bank_transfer, ussd) was used
        $transaction = Transaction::where(function($query) use ($reference) {
                $query->where('transaction_ref', $reference)
                      ->orWhere('payment_gateway_ref', $reference);
            })
            ->where('payment_method', 'paystack')
            ->where('status', 'pending') // Only process pending transactions
            ->first();

        if (!$transaction) {
            Log::warning('Paystack webhook: Transaction not found', ['reference' => $reference]);
            return;
        }

        // Update transaction with gateway reference and response
        $transaction->update([
            'payment_gateway_ref' => $reference,
            'gateway_response' => $data,
        ]);

        // Use the model's markAsPaid method
        $transaction->markAsPaid();

        // Handle different transaction types
        $this->handleTransactionable($transaction);

        Log::info('Paystack webhook: Payment processed successfully', [
            'reference' => $reference,
            'transaction_id' => $transaction->id,
        ]);
    }

    /**
     * Handle failed payment
     */
    protected function handleFailedPayment(array $data)
    {
        $reference = $data['reference'] ?? null;
        
        if (!$reference) {
            return;
        }

        $transaction = Transaction::where(function($query) use ($reference) {
                $query->where('transaction_ref', $reference)
                      ->orWhere('payment_gateway_ref', $reference);
            })
            ->where('payment_method', 'paystack')
            ->where('status', 'pending')
            ->first();

        if ($transaction) {
            // Update gateway reference if not set
            if (!$transaction->payment_gateway_ref) {
                $transaction->update(['payment_gateway_ref' => $reference]);
            }
            
            // Use the model's markAsFailed method
            $transaction->markAsFailed();
            $transaction->update(['gateway_response' => $data]);

            Log::info('Paystack webhook: Payment failed', [
                'reference' => $reference,
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
            Log::warning('Paystack webhook: Transaction has no transactionable', [
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
            Log::info('Paystack webhook: Unhandled transactionable type', [
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
            Log::warning('Paystack webhook: Subscription not found', [
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

        Log::info('Paystack webhook: Subscription activated', [
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
            Log::warning('Paystack webhook: Wallet not found', [
                'wallet_id' => $transaction->transactionable_id,
                'transaction_id' => $transaction->id,
            ]);
            return;
        }

        // Deposit funds to wallet
        $wallet->deposit(
            $transaction->amount,
            'Wallet funding via Paystack',
            $transaction
        );

        Log::info('Paystack webhook: Wallet funded', [
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
            Log::warning('Paystack webhook: Campaign not found', [
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

        Log::info('Paystack webhook: Campaign payment processed', [
            'campaign_id' => $campaign->id,
            'transaction_id' => $transaction->id,
        ]);
    }
}
