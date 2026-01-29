<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessReferralCreditTransaction;
use App\Models\Subscription;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Converts business referral credits to ad credits, quote credits, or 1-month subscription.
 */
class ReferralCreditConversionService
{
    public function __construct(
        protected EnsureBusinessSubscription $ensureBusinessSubscription
    ) {}

    /**
     * Convert referral credits to ad credits (1:1).
     *
     * @param int $credits Number of referral credits to convert
     * @return array{success: bool, message: string, wallet?: Wallet, transaction?: BusinessReferralCreditTransaction}
     */
    public function convertToAdCredits(Business $business, int $credits): array
    {
        if ($credits <= 0) {
            return ['success' => false, 'message' => 'Credits must be greater than zero.'];
        }

        if ($business->referral_credits < $credits) {
            return [
                'success' => false,
                'message' => "Insufficient referral credits. You have {$business->referral_credits}, need {$credits}.",
            ];
        }

        return DB::transaction(function () use ($business, $credits) {
            $business->decrement('referral_credits', $credits);
            $business->refresh();

            $wallet = Wallet::firstOrCreate(
                ['business_id' => $business->id],
                [
                    'user_id' => $business->user_id,
                    'balance' => 0,
                    'currency' => 'NGN',
                    'ad_credits' => 0,
                    'quote_credits' => 0,
                ]
            );

            $wallet->addCredits(
                $credits,
                "Referral credits converted to ad credits ({$credits})",
                null,
                null
            );

            $tx = BusinessReferralCreditTransaction::create([
                'business_id' => $business->id,
                'amount' => -$credits,
                'type' => BusinessReferralCreditTransaction::TYPE_CONVERTED_AD_CREDITS,
                'balance_after' => $business->referral_credits,
                'description' => "Converted {$credits} referral credits to ad credits",
                'reference_type' => Wallet::class,
                'reference_id' => $wallet->id,
            ]);

            Log::info('ReferralCreditConversionService: Converted to ad credits', [
                'business_id' => $business->id,
                'credits' => $credits,
                'wallet_id' => $wallet->id,
            ]);

            return [
                'success' => true,
                'message' => "{$credits} ad credits added to your wallet.",
                'wallet' => $wallet,
                'transaction' => $tx,
            ];
        });
    }

    /**
     * Convert referral credits to quote credits (1:1).
     *
     * @param int $credits Number of referral credits to convert
     * @return array{success: bool, message: string, wallet?: Wallet, transaction?: BusinessReferralCreditTransaction}
     */
    public function convertToQuoteCredits(Business $business, int $credits): array
    {
        if ($credits <= 0) {
            return ['success' => false, 'message' => 'Credits must be greater than zero.'];
        }

        if ($business->referral_credits < $credits) {
            return [
                'success' => false,
                'message' => "Insufficient referral credits. You have {$business->referral_credits}, need {$credits}.",
            ];
        }

        return DB::transaction(function () use ($business, $credits) {
            $business->decrement('referral_credits', $credits);
            $business->refresh();

            $wallet = Wallet::firstOrCreate(
                ['business_id' => $business->id],
                [
                    'user_id' => $business->user_id,
                    'balance' => 0,
                    'currency' => 'NGN',
                    'ad_credits' => 0,
                    'quote_credits' => 0,
                ]
            );

            $wallet->addQuoteCredits(
                $credits,
                "Referral credits converted to quote credits ({$credits})",
                null,
                null
            );

            $tx = BusinessReferralCreditTransaction::create([
                'business_id' => $business->id,
                'amount' => -$credits,
                'type' => BusinessReferralCreditTransaction::TYPE_CONVERTED_QUOTE_CREDITS,
                'balance_after' => $business->referral_credits,
                'description' => "Converted {$credits} referral credits to quote credits",
                'reference_type' => Wallet::class,
                'reference_id' => $wallet->id,
            ]);

            Log::info('ReferralCreditConversionService: Converted to quote credits', [
                'business_id' => $business->id,
                'credits' => $credits,
                'wallet_id' => $wallet->id,
            ]);

            return [
                'success' => true,
                'message' => "{$credits} quote credits added to your wallet.",
                'wallet' => $wallet,
                'transaction' => $tx,
            ];
        });
    }

    /**
     * Convert referral credits to 1-month subscription extension.
     * Uses config 'referral.conversion_to_subscription_credits' (default 500).
     *
     * @return array{success: bool, message: string, subscription?: Subscription, transaction?: BusinessReferralCreditTransaction}
     */
    public function convertToSubscription(Business $business): array
    {
        $creditsRequired = (int) config('referral.conversion_to_subscription_credits', 500);
        if ($creditsRequired <= 0) {
            return ['success' => false, 'message' => 'Subscription conversion is not configured.'];
        }

        if ($business->referral_credits < $creditsRequired) {
            return [
                'success' => false,
                'message' => "Insufficient referral credits. You need {$creditsRequired} for 1 month subscription.",
            ];
        }

        return DB::transaction(function () use ($business, $creditsRequired) {
            $subscription = $business->activeSubscription();
            if (!$subscription) {
                $subscription = $this->ensureBusinessSubscription->ensure($business);
            }
            if (!$subscription) {
                return ['success' => false, 'message' => 'Could not find or create a subscription to extend.'];
            }

            $business->decrement('referral_credits', $creditsRequired);
            $business->refresh();

            $startDate = $subscription->ends_at && $subscription->ends_at->isFuture()
                ? $subscription->ends_at
                : now();
            $subscription->update([
                'ends_at' => $startDate->copy()->addDays(30),
                'status' => 'active',
            ]);
            $subscription->refresh();

            $tx = BusinessReferralCreditTransaction::create([
                'business_id' => $business->id,
                'amount' => -$creditsRequired,
                'type' => BusinessReferralCreditTransaction::TYPE_CONVERTED_SUBSCRIPTION,
                'balance_after' => $business->referral_credits,
                'description' => 'Converted referral credits to 1-month subscription',
                'reference_type' => Subscription::class,
                'reference_id' => $subscription->id,
            ]);

            Log::info('ReferralCreditConversionService: Converted to subscription', [
                'business_id' => $business->id,
                'credits_used' => $creditsRequired,
                'subscription_id' => $subscription->id,
                'new_ends_at' => $subscription->ends_at->toDateTimeString(),
            ]);

            return [
                'success' => true,
                'message' => 'Your subscription has been extended by 1 month.',
                'subscription' => $subscription,
                'transaction' => $tx,
            ];
        });
    }
}
