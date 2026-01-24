<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Subscription;
use App\Models\AdCampaign;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;

/**
 * Activation Service
 *
 * Single place for completing transactions and activating payables
 * (Subscription, AdCampaign, Wallet). Used by webhooks, callbacks,
 * wallet payments, and admin "mark as completed".
 */
class ActivationService
{
    /**
     * Complete a transaction and activate its payable.
     * Idempotent: skips if already processed (paid_at set).
     */
    public function completeAndActivate(Transaction $transaction): void
    {
        $transaction->refresh();

        if ($transaction->paid_at) {
            Log::info('ActivationService: Transaction already processed (idempotent skip)', [
                'transaction_id' => $transaction->id,
            ]);
            return;
        }

        $transaction->markAsPaid();
        $this->activatePayable($transaction);
    }

    /**
     * Activate the payable (Subscription, AdCampaign, Wallet) for a transaction.
     */
    protected function activatePayable(Transaction $transaction): void
    {
        $payable = $transaction->transactionable;

        if (!$payable) {
            Log::warning('ActivationService: Transaction has no payable', [
                'transaction_id' => $transaction->id,
            ]);
            return;
        }

        match (true) {
            $payable instanceof Subscription => $this->activateSubscription($payable, $transaction),
            $payable instanceof AdCampaign => $this->activateAdCampaign($payable, $transaction),
            $payable instanceof Wallet => $this->activateWallet($payable, $transaction),
            default => Log::warning('ActivationService: Unknown payable type', [
                'type' => get_class($payable),
                'transaction_id' => $transaction->id,
            ]),
        };

        Log::info('ActivationService: Payable activated', [
            'transaction_id' => $transaction->id,
            'type' => get_class($payable),
            'payable_id' => $payable->id,
        ]);
    }

    /**
     * Activate wallet: deposit (funding) or addCredits (credit purchase).
     */
    protected function activateWallet(Wallet $wallet, Transaction $transaction): void
    {
        $metadata = $transaction->metadata ?? [];
        $isCreditPurchase = ($metadata['type'] ?? null) === 'credit_purchase';
        $credits = (int) ($metadata['credits'] ?? 0);

        if ($isCreditPurchase && $credits > 0) {
            $wallet->addCredits(
                $credits,
                "Ad credits purchase - {$credits} credits",
                $transaction
            );
            Log::info('ActivationService: Wallet credits added (credit purchase)', [
                'wallet_id' => $wallet->id,
                'credits' => $credits,
                'transaction_id' => $transaction->id,
            ]);
        } else {
            $wallet->deposit(
                $transaction->amount,
                'Payment gateway funding',
                $transaction
            );
        }
    }

    /**
     * Activate subscription (new or renewal/extension).
     */
    protected function activateSubscription(Subscription $subscription, Transaction $transaction): void
    {
        $metadata = $transaction->metadata ?? [];
        $isRenewalOrExtension = ($metadata['type'] ?? null) === 'subscription_renewal';

        if ($isRenewalOrExtension) {
            $oldEndDate = ($subscription->ends_at && $subscription->ends_at->isFuture())
                ? $subscription->ends_at->copy()
                : now();
            $wasExpired = $subscription->isExpired() || $subscription->status === 'expired';

            $subscription->renew();

            $daysRemaining = now()->diffInDays($oldEndDate, false);
            $actionType = $daysRemaining > 90 ? 'extended' : 'renewed';

            Log::info("ActivationService: Subscription {$actionType}", [
                'subscription_id' => $subscription->id,
                'transaction_id' => $transaction->id,
                'was_expired' => $wasExpired,
                'days_remaining_before' => max(0, $daysRemaining),
                'old_end_date' => $oldEndDate->toDateTimeString(),
                'new_end_date' => $subscription->ends_at->toDateTimeString(),
                'action' => $actionType,
            ]);
        } else {
            $subscription->update(['status' => 'active']);

            Log::info('ActivationService: New subscription activated', [
                'subscription_id' => $subscription->id,
                'transaction_id' => $transaction->id,
                'end_date' => $subscription->ends_at?->toDateTimeString(),
            ]);
        }

        $business = $subscription->business;

        if (!$business) {
            Log::warning('ActivationService: Subscription has no business', [
                'subscription_id' => $subscription->id,
            ]);
            return;
        }

        if ($business->is_verified) {
            $business->update([
                'is_premium' => true,
                'premium_until' => $subscription->ends_at,
            ]);

            Log::info('ActivationService: Premium granted', [
                'subscription_id' => $subscription->id,
                'business_id' => $business->id,
                'premium_until' => $subscription->ends_at->toDateTimeString(),
            ]);
        } else {
            Log::info('ActivationService: Subscription active, premium not granted (business not verified)', [
                'subscription_id' => $subscription->id,
                'business_id' => $business->id,
            ]);
        }
    }

    /**
     * Activate ad campaign or process extension/budget addition.
     */
    protected function activateAdCampaign(AdCampaign $campaign, Transaction $transaction): void
    {
        $metadata = $transaction->metadata ?? [];
        $extensionType = $metadata['extension_type'] ?? null;

        if ($extensionType === 'duration') {
            $days = (int) ($metadata['days'] ?? 0);
            if ($days > 0 && $campaign->ends_at) {
                $campaign->update([
                    'ends_at' => $campaign->ends_at->copy()->addDays($days),
                ]);

                Log::info('ActivationService: Campaign duration extended', [
                    'campaign_id' => $campaign->id,
                    'days_added' => $days,
                    'new_end_date' => $campaign->ends_at->toDateTimeString(),
                    'transaction_id' => $transaction->id,
                ]);
            }
        } elseif ($extensionType === 'budget') {
            $additionalBudget = (float) ($metadata['additional_budget'] ?? 0);
            if ($additionalBudget > 0) {
                $campaign->update([
                    'budget' => $campaign->budget + $additionalBudget,
                ]);

                Log::info('ActivationService: Campaign budget increased', [
                    'campaign_id' => $campaign->id,
                    'budget_added' => $additionalBudget,
                    'new_budget' => (float) $campaign->budget,
                    'transaction_id' => $transaction->id,
                ]);
            }
        } else {
            $campaign->update([
                'is_paid' => true,
                'is_active' => true,
            ]);

            Log::info('ActivationService: Campaign activated', [
                'campaign_id' => $campaign->id,
                'transaction_id' => $transaction->id,
            ]);
        }
    }
}
