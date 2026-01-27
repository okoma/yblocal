<?php

// ============================================
// app/Console/Commands/CheckExpiredSubscriptions.php
// Check for expired subscriptions and optionally attempt auto-renewal
// ============================================
namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\PaymentGateway;
use App\Services\PaymentService;
use App\Services\EnsureBusinessSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

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
        $this->info('Checking for subscriptions...');

        // Step 1: Handle auto-renewals for subscriptions expiring soon (if --auto-renew flag is present)
        if ($this->option('auto-renew')) {
            $this->processAutoRenewals();
        }

        // Step 2: Process already expired subscriptions
        $this->processExpiredSubscriptions();

        return Command::SUCCESS;
    }

    /**
     * Process auto-renewals for subscriptions expiring soon
     */
    protected function processAutoRenewals(): void
    {
        $this->info('Processing auto-renewals for subscriptions expiring soon...');

        // Find subscriptions that:
        // 1. Have auto_renew enabled
        // 2. Are currently active
        // 3. Expire within the next 3 days (before they actually expire)
        // 4. Haven't expired yet
        $expiringSubscriptions = Subscription::where('status', 'active')
            ->where('auto_renew', true)
            ->whereBetween('ends_at', [now(), now()->addDays(3)])
            ->with(['user', 'business', 'plan'])
            ->get();

        $this->info("Found {$expiringSubscriptions->count()} subscriptions for auto-renewal");

        $renewedCount = 0;
        $failedCount = 0;

        foreach ($expiringSubscriptions as $subscription) {
            $daysRemaining = $subscription->daysRemaining();
            $action = $daysRemaining > 90 ? 'extension' : 'renewal';
            
            $this->info("Attempting auto-{$action} for subscription #{$subscription->id} (expires in {$daysRemaining} days)");
            
            $result = $this->attemptAutoRenewal($subscription);

            if ($result['success']) {
                $renewedCount++;
                $this->info("✓ Auto-{$action} successful for subscription #{$subscription->id}");
            } else {
                $failedCount++;
                $this->warn("✗ Auto-{$action} failed for subscription #{$subscription->id}: {$result['reason']}");
            }
        }

        $this->info("Auto-renewal results: {$renewedCount} succeeded, {$failedCount} failed");
    }

    /**
     * Process subscriptions that have already expired
     */
    protected function processExpiredSubscriptions(): void
    {
        $this->info('Processing expired subscriptions...');

        // Find all active OR cancelled subscriptions that have already expired
        $expiredSubscriptions = Subscription::whereIn('status', ['active', 'cancelled'])
            ->where('ends_at', '<=', now())
            ->with(['user', 'business', 'plan'])
            ->get();

        $this->info("Found {$expiredSubscriptions->count()} expired subscriptions");

        $expiredCount = 0;
        $ensureService = app(EnsureBusinessSubscription::class);

        foreach ($expiredSubscriptions as $subscription) {
            // Mark subscription as expired
            $subscription->update(['status' => 'expired']);

            // Remove premium status from business
            if ($subscription->business) {
                $subscription->business->update([
                    'is_premium' => false,
                    'premium_until' => null,
                ]);

                $this->info("✓ Expired subscription #{$subscription->id} - Removed premium from business #{$subscription->business->id}");

                Log::info('Premium removed due to subscription expiry', [
                    'subscription_id' => $subscription->id,
                    'business_id' => $subscription->business->id,
                    'expired_at' => $subscription->ends_at,
                ]);

                // Ensure business has an active subscription (assign free plan if needed)
                $newSubscription = $ensureService->ensure($subscription->business);
                if ($newSubscription) {
                    $this->info("  → Assigned free plan subscription #{$newSubscription->id} to business #{$subscription->business->id}");
                }

                // Notify user about expiration
                $this->notifyUserOfExpiration($subscription);

                $expiredCount++;
            }
        }

        $this->info("Processed {$expiredCount} expired subscriptions");
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

            // Get user's wallet payment gateway
            $walletGateway = PaymentGateway::where('slug', 'wallet')
                ->where('is_active', true)
                ->where('is_enabled', true)
                ->first();

            if (!$walletGateway) {
                Log::warning('Wallet gateway not available for auto-renewal', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                ]);

                $this->handleAutoRenewalFailure($subscription, 'Wallet payment gateway not available');
                return ['success' => false, 'reason' => 'Wallet payment gateway not available'];
            }

            // Get wallet from subscription's business (wallets are now business-scoped)
            $wallet = \App\Models\Wallet::where('business_id', $subscription->business_id)->first();
            
            // Check if user has sufficient wallet balance
            if (!$wallet || $wallet->balance < $amount) {
                $balance = $wallet ? $wallet->balance : 0;
                $shortfall = $amount - $balance;

                Log::info('Insufficient wallet balance for auto-renewal', [
                    'subscription_id' => $subscription->id,
                    'business_id' => $subscription->business_id,
                    'user_id' => $user->id,
                    'required' => $amount,
                    'available' => $balance,
                    'shortfall' => $shortfall,
                ]);

                $this->handleAutoRenewalFailure($subscription, "Insufficient wallet balance (need ₦" . number_format($shortfall, 2) . " more)");
                return ['success' => false, 'reason' => 'Insufficient wallet balance'];
            }

            // Process wallet renewal
            return $this->processWalletRenewal($subscription, $user, $amount, $walletGateway);

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
    protected function processWalletRenewal(Subscription $subscription, $user, float $amount, PaymentGateway $gateway): array
    {
        try {
            DB::beginTransaction();

            // Use PaymentService to process wallet payment
            $paymentService = app(PaymentService::class);
            $result = $paymentService->initializePayment(
                user: $user,
                amount: $amount,
                gatewayId: $gateway->id,
                payable: $subscription,
                metadata: [
                    'type' => 'subscription_renewal',
                    'subscription_id' => $subscription->id,
                    'plan_id' => $subscription->subscription_plan_id,
                    'billing_interval' => $subscription->billing_interval,
                    'auto_renewal' => true,
                    'renewal_date' => now()->toIso8601String(),
                ]
            );

            if ($result->isSuccess()) {
                // Payment successful via wallet - subscription is already renewed by PaymentService
                DB::commit();

                $daysRemaining = $subscription->daysRemaining();
                $action = $daysRemaining > 90 ? 'extended' : 'renewed';

                Log::info("Auto-{$action} successful via wallet", [
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'new_end_date' => $subscription->fresh()->ends_at,
                ]);

                // Send success notification
                $this->notifyUserOfSuccess($subscription, $action);

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
     * Handle auto-renewal failure
     */
    protected function handleAutoRenewalFailure(Subscription $subscription, string $reason): void
    {
        // Disable auto-renew after failure
        $subscription->update([
            'auto_renew' => false,
        ]);

        Log::warning('Auto-renewal disabled due to failure', [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'reason' => $reason,
        ]);

        // Send failure notification to user
        $this->notifyUserOfFailure($subscription, $reason);
    }

    /**
     * Notify user of successful auto-renewal
     */
    protected function notifyUserOfSuccess(Subscription $subscription, string $action): void
    {
        $user = $subscription->user;
        $period = $subscription->isYearly() ? '1 year' : '1 month';

        Notification::make()
            ->success()
            ->title('Subscription Auto-' . ucfirst($action))
            ->body("Your {$subscription->plan->name} subscription has been automatically {$action} for {$period}. " .
                   "New expiration: " . $subscription->fresh()->ends_at->format('M j, Y'))
            ->sendToDatabase($user);

        Log::info('Auto-renewal success notification sent', [
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Notify user of auto-renewal failure
     */
    protected function notifyUserOfFailure(Subscription $subscription, string $reason): void
    {
        $user = $subscription->user;

        Notification::make()
            ->warning()
            ->title('Auto-Renewal Failed')
            ->body("We couldn't automatically renew your {$subscription->plan->name} subscription. " .
                   "Reason: {$reason}. " .
                   "Auto-renewal has been disabled. Please renew manually before " .
                   $subscription->ends_at->format('M j, Y') . ".")
            ->actions([
                \Filament\Notifications\Actions\Action::make('renew')
                    ->button()
                    ->url(\App\Filament\Business\Resources\SubscriptionResource::getUrl('view', ['record' => $subscription->id], panel: 'business'))
            ])
            ->sendToDatabase($user);

        Log::info('Auto-renewal failure notification sent', [
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Notify user of subscription expiration
     */
    protected function notifyUserOfExpiration(Subscription $subscription): void
    {
        $user = $subscription->user;

        Notification::make()
            ->danger()
            ->title('Subscription Expired')
            ->body("Your {$subscription->plan->name} subscription has expired. " .
                   "Premium features have been disabled.")
            ->actions([
                \Filament\Notifications\Actions\Action::make('renew')
                    ->button()
                    ->url(\App\Filament\Business\Resources\SubscriptionResource::getUrl('view', ['record' => $subscription->id], panel: 'business'))
            ])
            ->sendToDatabase($user);

        Log::info('Subscription expiration notification sent', [
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
        ]);
    }
}