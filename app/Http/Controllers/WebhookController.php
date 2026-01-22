<?php

namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Models\Subscription;
use App\Models\Wallet;
use App\Models\AdCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Unified Webhook Controller
 * 
 * Handles webhooks from ALL payment gateways (Paystack, Flutterwave, etc.)
 * Gateway-specific logic is handled through methods based on gateway slug
 */
class WebhookController extends Controller
{
    /**
     * Handle webhook for any payment gateway
     * 
     * @param Request $request
     * @param string $gateway (paystack, flutterwave, etc.)
     */
    public function handle(Request $request, string $gateway)
    {
        // Get gateway configuration
        $gatewayModel = PaymentGateway::where('slug', $gateway)
            ->where('is_enabled', true)
            ->first();

        if (!$gatewayModel || !$gatewayModel->secret_key) {
            Log::error("Webhook [{$gateway}]: Gateway not configured");
            return response()->json(['error' => 'Gateway not configured'], 400);
        }

        // Verify webhook signature (gateway-specific)
        if (!$this->verifyWebhookSignature($request, $gateway, $gatewayModel->secret_key)) {
            Log::error("Webhook [{$gateway}]: Invalid signature");
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Parse webhook data (gateway-specific)
        $webhookData = $this->parseWebhookData($request, $gateway);
        
        if (!$webhookData || !isset($webhookData['event'])) {
            Log::error("Webhook [{$gateway}]: Invalid event data");
            return response()->json(['error' => 'Invalid event'], 400);
        }

        // Handle event based on type
        $this->handleWebhookEvent($webhookData, $gateway);

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Verify webhook signature based on gateway
     */
    protected function verifyWebhookSignature(Request $request, string $gateway, string $secret): bool
    {
        return match ($gateway) {
            'paystack' => $this->verifyPaystackSignature($request, $secret),
            'flutterwave' => $this->verifyFlutterwaveSignature($request, $secret),
            default => false,
        };
    }

    /**
     * Verify Paystack webhook signature
     */
    protected function verifyPaystackSignature(Request $request, string $secret): bool
    {
        $signature = $request->header('x-paystack-signature');
        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $computedSignature = hash_hmac('sha512', $payload, $secret);
        
        return hash_equals($computedSignature, $signature);
    }

    /**
     * Verify Flutterwave webhook signature
     */
    protected function verifyFlutterwaveSignature(Request $request, string $secret): bool
    {
        $signature = $request->header('verif-hash');
        if (!$signature) {
            return false;
        }

        $payload = $request->all();
        $computedHash = hash_hmac('sha256', json_encode($payload), $secret);
        
        return hash_equals($computedHash, $signature);
    }

    /**
     * Parse webhook data based on gateway
     */
    protected function parseWebhookData(Request $request, string $gateway): ?array
    {
        return match ($gateway) {
            'paystack' => $this->parsePaystackWebhook($request),
            'flutterwave' => $this->parseFlutterwaveWebhook($request),
            default => null,
        };
    }

    /**
     * Parse Paystack webhook data
     */
    protected function parsePaystackWebhook(Request $request): ?array
    {
        $event = json_decode($request->getContent(), true);
        
        return [
            'event' => $event['event'] ?? null,
            'data' => $event['data'] ?? [],
        ];
    }

    /**
     * Parse Flutterwave webhook data
     */
    protected function parseFlutterwaveWebhook(Request $request): ?array
    {
        $payload = $request->all();
        
        return [
            'event' => $payload['event'] ?? null,
            'data' => $payload['data'] ?? [],
        ];
    }

    /**
     * Handle webhook event based on type
     */
    protected function handleWebhookEvent(array $webhookData, string $gateway): void
    {
        $event = $webhookData['event'];
        $data = $webhookData['data'];

        // Determine if it's success or failure
        $isSuccess = match ($gateway) {
            'paystack' => $event === 'charge.success',
            'flutterwave' => in_array($event, ['charge.completed', 'charge.succeeded']),
            default => false,
        };

        $isFailure = match ($gateway) {
            'paystack' => $event === 'charge.failed',
            'flutterwave' => $event === 'charge.failed',
            default => false,
        };

        if ($isSuccess) {
            $this->handleSuccessfulPayment($data, $gateway);
        } elseif ($isFailure) {
            $this->handleFailedPayment($data, $gateway);
        } else {
            Log::info("Webhook [{$gateway}]: Unhandled event", ['event' => $event]);
        }
    }

    /**
     * Extract transaction reference from webhook data
     */
    protected function extractReference(array $data, string $gateway): ?string
    {
        return match ($gateway) {
            'paystack' => $data['reference'] ?? null,
            'flutterwave' => $data['tx_ref'] ?? $data['flw_ref'] ?? null,
            default => null,
        };
    }

    /**
     * Handle successful payment
     */
    protected function handleSuccessfulPayment(array $data, string $gateway): void
    {
        $reference = $this->extractReference($data, $gateway);
        
        if (!$reference) {
            Log::error("Webhook [{$gateway}]: Missing reference in successful payment");
            return;
        }

        // Find transaction by reference
        $transaction = Transaction::where(function($query) use ($reference) {
                $query->where('transaction_ref', $reference)
                      ->orWhere('payment_gateway_ref', $reference);
            })
            ->where('payment_method', $gateway)
            ->where('status', 'pending')
            ->first();

        if (!$transaction) {
            Log::warning("Webhook [{$gateway}]: Transaction not found", ['reference' => $reference]);
            return;
        }

        // Update transaction
        $transaction->update([
            'payment_gateway_ref' => $reference,
            'gateway_response' => $data,
        ]);

        // Mark as paid
        $transaction->markAsPaid();

        // Activate the transactionable (Subscription, Wallet, AdCampaign)
        $this->activateTransactionable($transaction, $gateway);

        Log::info("Webhook [{$gateway}]: Payment processed successfully", [
            'reference' => $reference,
            'transaction_id' => $transaction->id,
        ]);
    }

    /**
     * Handle failed payment
     */
    protected function handleFailedPayment(array $data, string $gateway): void
    {
        $reference = $this->extractReference($data, $gateway);
        
        if (!$reference) {
            return;
        }

        $transaction = Transaction::where(function($query) use ($reference) {
                $query->where('transaction_ref', $reference)
                      ->orWhere('payment_gateway_ref', $reference);
            })
            ->where('payment_method', $gateway)
            ->where('status', 'pending')
            ->first();

        if ($transaction) {
            if (!$transaction->payment_gateway_ref) {
                $transaction->update(['payment_gateway_ref' => $reference]);
            }
            
            $transaction->markAsFailed();
            $transaction->update(['gateway_response' => $data]);

            Log::info("Webhook [{$gateway}]: Payment failed", [
                'reference' => $reference,
                'transaction_id' => $transaction->id,
            ]);
        }
    }

    /**
     * Activate transactionable based on type
     */
    protected function activateTransactionable(Transaction $transaction, string $gateway): void
    {
        $transactionable = $transaction->transactionable;
        
        if (!$transactionable) {
            Log::warning("Webhook [{$gateway}]: Transaction has no transactionable", [
                'transaction_id' => $transaction->id,
            ]);
            return;
        }

        // Handle based on type
        if ($transactionable instanceof Subscription) {
            $this->activateSubscription($transactionable, $transaction, $gateway);
        } elseif ($transactionable instanceof Wallet) {
            $this->fundWallet($transactionable, $transaction, $gateway);
        } elseif ($transactionable instanceof AdCampaign) {
            $this->activateCampaign($transactionable, $transaction, $gateway);
        }
    }

    /**
     * Activate subscription
     */
    protected function activateSubscription(Subscription $subscription, Transaction $transaction, string $gateway): void
    {
        $subscription->update([
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays(30), // Default, adjust as needed
        ]);

        Log::info("Webhook [{$gateway}]: Subscription activated", [
            'subscription_id' => $subscription->id,
            'transaction_id' => $transaction->id,
        ]);
    }

    /**
     * Fund wallet
     */
    protected function fundWallet(Wallet $wallet, Transaction $transaction, string $gateway): void
    {
        $wallet->deposit(
            $transaction->amount,
            "Wallet funding via " . ucfirst($gateway),
            $transaction
        );

        Log::info("Webhook [{$gateway}]: Wallet funded", [
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'amount' => $transaction->amount,
            'transaction_id' => $transaction->id,
        ]);
    }

    /**
     * Activate campaign
     */
    protected function activateCampaign(AdCampaign $campaign, Transaction $transaction, string $gateway): void
    {
        $campaign->update([
            'is_paid' => true,
            'is_active' => true,
            'transaction_id' => $transaction->id,
        ]);

        Log::info("Webhook [{$gateway}]: Campaign activated", [
            'campaign_id' => $campaign->id,
            'transaction_id' => $transaction->id,
        ]);
    }
}
