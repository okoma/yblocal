<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\PaymentGateway;
use App\Services\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CheckExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check-expired {--auto-renew}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired subscriptions and optionally attempt auto-renewal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired subscriptions...');

        // Find all active subscriptions that have expired
        $expiredSubscriptions = Subscription::where('status', 'active')
            ->where('ends_at', '<=', now())
            ->with(['user', 'business', 'plan'])
            ->get();

        $expiredCount = 0;
        $renewedCount = 0;
        $renewalFailedCount = 0;

        foreach ($expiredSubscriptions as $subscription) {
            // Check if auto-renew is enabled and we should attempt renewal
            if ($this->option('auto-renew') && $subscription->auto_renew) {
                $result = $this->attemptAutoRenewal($subscription);
                
                if ($result['success']) {
                    $renewedCount++;
                    $this->info("✓ Auto-renewed subscription #{$subscription->id} for user {$subscription->user->name}");
                    continue;
                } else {
                    $renewalFailedCount++;
                    $this->warn("✗ Auto-renewal failed for subscription #{$subscription->id}: {$result['reason']}");
                }
            }

            // Mark subscription as expired
            $subscription->update(['status' => 'expired']);

            // Remove premium status from business
            if ($subscription->business) {
                $subscription->business->update([
                    'is_premium' => false,
                    'premium_until' => null,
                ]);

                $this->info("Removed premium from business #{$subscription->business->id} ({$subscription->business->business_name})");
                
                Log::info('Premium removed due to subscription expiry', [
                    'subscription_id' => $subscription->id,
                    'business_id' => $subscription->business->id,
                    'expired_at' => $subscription->ends_at,
                ]);

                $expiredCount++;
            }
        }

        $this->info("Processed {$expiredCount} expired subscriptions.");
        if ($this->option('auto-renew')) {
            $this->info("Auto-renewed: {$renewedCount}, Failed: {$renewalFailedCount}");
        }

        return 0;
    }

    /**
     * Attempt to auto-renew a subscription
     */
    protected function attemptAutoRenewal(Subscription $subscription): array
    {
        try {
            $user = $subscription->user;
            
            if (!$user) {
                return ['success' => false, 'reason' => 'User not found'];
            }

            // Get the subscription price
            $amount = $subscription->getPrice();
            
            if ($amount <= 0) {
                return ['success' => false, 'reason' => 'Invalid subscription price'];
            }

            // Get user's default payment gateway (last used or first available)
            $gateway = $this->getDefaultPaymentGateway($user, $subscription);
            
            if (!$gateway) {
                Log::warning('No payment gateway available for auto-renewal', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                ]);
                
                // Disable auto-renew after 3 failed attempts
                $this->handleAutoRenewalFailure($subscription, 'No payment method available');
                
                return ['success' => false, 'reason' => 'No payment method available'];
            }

            // Check if user has wallet with sufficient balance (preferred for auto-renewal)
            if ($gateway->isWallet() && $user->wallet && $user->wallet->balance >= $amount) {
                return $this->processWalletRenewal($subscription, $user, $amount);
            }

            // For other payment methods, we can't auto-process without user interaction
            // So we'll notify the user and disable auto-renew
            Log::info('Auto-renewal requires user interaction for non-wallet payment', [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'gateway' => $gateway->slug,
            ]);

            // TODO: Send notification to user about pending renewal
            // For now, disable auto-renew and let user manually renew
            $this->handleAutoRenewalFailure($subscription, 'Payment method requires user interaction');

            return ['success' => false, 'reason' => 'Payment method requires user interaction'];

        } catch (\Exception $e) {
            Log::error('Auto-renewal exception', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->handleAutoRenewalFailure($subscription, $e->getMessage());
            
            return ['success' => false, 'reason' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Process renewal using wallet
     */
    protected function processWalletRenewal(Subscription $subscription, $user, float $amount): array
    {
        try {
            DB::beginTransaction();

            // Use PaymentService to process wallet payment
            $paymentService = app(PaymentService::class);
            $result = $paymentService->initializePayment(
                user: $user,
                amount: $amount,
                gatewayId: PaymentGateway::where('slug', 'wallet')->first()->id,
                payable: $subscription,
                metadata: [
                    'auto_renewal' => true,
                    'renewal_date' => now()->toIso8601String(),
                ]
            );

            if ($result->isSuccess()) {
                // Payment successful - subscription will be renewed via PaymentController
                DB::commit();
                
                Log::info('Auto-renewal successful via wallet', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                    'amount' => $amount,
                ]);

                return ['success' => true];
            } else {
                DB::rollBack();
                
                $this->handleAutoRenewalFailure($subscription, $result->message ?? 'Payment failed');
                
                return ['success' => false, 'reason' => $result->message ?? 'Payment failed'];
            }

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get default payment gateway for user
     */
    protected function getDefaultPaymentGateway($user, Subscription $subscription): ?PaymentGateway
    {
        // Prefer wallet if user has sufficient balance
        $walletGateway = PaymentGateway::where('slug', 'wallet')
            ->where('is_active', true)
            ->where('is_enabled', true)
            ->first();
            
        if ($walletGateway && $user->wallet && $user->wallet->balance >= $subscription->getPrice()) {
            return $walletGateway;
        }

        // Otherwise, get the last used payment method from subscription
        if ($subscription->payment_method) {
            $gateway = PaymentGateway::where('slug', $subscription->payment_method)
                ->where('is_active', true)
                ->where('is_enabled', true)
                ->first();
                
            if ($gateway) {
                return $gateway;
            }
        }

        // Fallback to first available gateway
        return PaymentGateway::enabled()->ordered()->first();
    }

    /**
     * Handle auto-renewal failure
     */
    protected function handleAutoRenewalFailure(Subscription $subscription, string $reason): void
    {
        // Track failure count in metadata or separate field
        // For now, disable auto-renew after first failure
        // In production, you might want to track attempts and disable after 3
        
        $subscription->update([
            'auto_renew' => false,
        ]);

        Log::warning('Auto-renewal disabled due to failure', [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'reason' => $reason,
        ]);

        // TODO: Send notification to user about failed auto-renewal
    }
}
