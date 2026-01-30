<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;

/**
 * Helper class to access referral configuration values.
 * Provides type-safe, convenient access to all referral settings.
 */
class ReferralConfig
{
    /**
     * Get customer commission rate (0-1 decimal)
     */
    public static function customerCommissionRate(): float
    {
        return (float) config('referral.customer_commission_rate', 0.10);
    }

    /**
     * Get minimum commission amount in NGN
     */
    public static function minCommissionAmount(): float
    {
        return (float) config('referral.min_commission_amount', 100);
    }

    /**
     * Get maximum commission per transaction in NGN
     */
    public static function maxCommissionPerTransaction(): float
    {
        return (float) config('referral.max_commission_per_transaction', 50000);
    }

    /**
     * Get eligible transaction types for commission
     */
    public static function eligibleTransactionTypes(): array
    {
        return config('referral.eligible_transaction_types', [
            'subscription',
            'ad_credits',
            'quote_credits',
        ]);
    }

    /**
     * Check if transaction type is eligible for commission
     */
    public static function isEligibleTransactionType(string $type): bool
    {
        return in_array($type, static::eligibleTransactionTypes(), true);
    }

    /**
     * Get business credits awarded per signup
     */
    public static function businessCreditsPerSignup(): int
    {
        return (int) config('referral.business_credits_per_signup', 100);
    }

    /**
     * Check if business qualification is enabled
     */
    public static function isQualificationEnabled(): bool
    {
        return (bool) config('referral.business_qualification.enabled', true);
    }

    /**
     * Get business qualification criteria
     */
    public static function qualificationCriteria(): array
    {
        return config('referral.business_qualification', []);
    }

    /**
     * Get minimum withdrawal amount in NGN
     */
    public static function minWithdrawalAmount(): float
    {
        return (float) config('referral.withdrawal.min_amount', 5000);
    }

    /**
     * Get maximum withdrawal per request in NGN
     */
    public static function maxWithdrawalPerRequest(): float
    {
        return (float) config('referral.withdrawal.max_amount_per_request', 500000);
    }

    /**
     * Get daily withdrawal limit in NGN
     */
    public static function dailyWithdrawalLimit(): float
    {
        return (float) config('referral.withdrawal.daily_limit', 1000000);
    }

    /**
     * Get monthly withdrawal limit in NGN
     */
    public static function monthlyWithdrawalLimit(): float
    {
        return (float) config('referral.withdrawal.monthly_limit', 5000000);
    }

    /**
     * Calculate withdrawal fee for given amount
     */
    public static function calculateWithdrawalFee(float $amount): float
    {
        $percentage = (float) config('referral.withdrawal.processing_fee_percentage', 1.5) / 100;
        $fee = $amount * $percentage;
        
        $minFee = (float) config('referral.withdrawal.min_processing_fee', 50);
        $maxFee = (float) config('referral.withdrawal.max_processing_fee', 2000);
        
        return max($minFee, min($fee, $maxFee));
    }

    /**
     * Get net withdrawal amount after fees
     */
    public static function calculateNetWithdrawal(float $amount): float
    {
        return $amount - static::calculateWithdrawalFee($amount);
    }

    /**
     * Get credits required for subscription conversion
     */
    public static function creditsForSubscription(): int
    {
        return (int) config('referral.conversion_limits.subscription.credits_required', 500);
    }

    /**
     * Get minimum credits for ad credits conversion
     */
    public static function minCreditsForAdConversion(): int
    {
        return (int) config('referral.conversion_limits.ad_credits.min_conversion', 50);
    }

    /**
     * Get minimum credits for quote credits conversion
     */
    public static function minCreditsForQuoteConversion(): int
    {
        return (int) config('referral.conversion_limits.quote_credits.min_conversion', 50);
    }

    /**
     * Check if fraud detection is enabled
     */
    public static function isFraudDetectionEnabled(): bool
    {
        return (bool) config('referral.fraud_detection.enabled', true);
    }

    /**
     * Get maximum referrals per IP
     */
    public static function maxReferralsPerIp(): int
    {
        return (int) config('referral.fraud_detection.max_referrals_per_ip', 3);
    }

    /**
     * Get maximum referrals per device
     */
    public static function maxReferralsPerDevice(): int
    {
        return (int) config('referral.fraud_detection.max_referrals_per_device', 5);
    }

    /**
     * Get minimum time between referrals in seconds
     */
    public static function minTimeBetweenReferrals(): int
    {
        return (int) config('referral.fraud_detection.min_time_between_referrals', 3600);
    }

    /**
     * Check if credits expire
     */
    public static function doCreditsExpire(): bool
    {
        return (bool) config('referral.expiration.credits_expire', true);
    }

    /**
     * Get credits expiration days
     */
    public static function creditsExpirationDays(): int
    {
        return (int) config('referral.expiration.credits_expiration_days', 365);
    }

    /**
     * Get business register URL
     */
    public static function businessRegisterUrl(): string
    {
        return config('referral.urls.business_register', 'https://biz.yellowbooks.ng/register');
    }

    /**
     * Generate customer referral link
     */
    public static function generateCustomerReferralLink(string $referralCode): string
    {
        return static::businessRegisterUrl() . '?ref=' . urlencode($referralCode);
    }

    /**
     * Generate business referral link
     */
    public static function generateBusinessReferralLink(string $referralCode): string
    {
        return static::businessRegisterUrl() . '?ref=' . urlencode($referralCode);
    }

    /**
     * Get share template for customer
     */
    public static function getCustomerShareTemplate(string $channel): ?string
    {
        return config("referral.share_templates.customer.{$channel}");
    }

    /**
     * Get share template for business
     */
    public static function getBusinessShareTemplate(string $channel): ?string
    {
        return config("referral.share_templates.business.{$channel}");
    }

    /**
     * Format share message with variables
     */
    public static function formatShareMessage(
        string $template,
        string $code,
        string $link,
        ?string $name = null,
        ?string $businessName = null
    ): string {
        return str_replace(
            ['{CODE}', '{LINK}', '{NAME}', '{BUSINESS_NAME}'],
            [$code, $link, $name ?? 'Friend', $businessName ?? 'My Business'],
            $template
        );
    }

    /**
     * Check if gamification is enabled
     */
    public static function isGamificationEnabled(): bool
    {
        return (bool) config('referral.gamification.enabled', true);
    }

    /**
     * Get referral tiers
     */
    public static function getTiers(): array
    {
        return config('referral.gamification.tiers', []);
    }

    /**
     * Get tier by referrals count
     */
    public static function getTierByReferralCount(int $referralCount): ?array
    {
        $tiers = static::getTiers();
        $currentTier = null;
        
        foreach ($tiers as $key => $tier) {
            if ($referralCount >= $tier['referrals_required']) {
                $currentTier = array_merge($tier, ['key' => $key]);
            }
        }
        
        return $currentTier;
    }

    /**
     * Get next tier by current referral count
     */
    public static function getNextTier(int $currentReferralCount): ?array
    {
        $tiers = static::getTiers();
        
        foreach ($tiers as $key => $tier) {
            if ($currentReferralCount < $tier['referrals_required']) {
                return array_merge($tier, ['key' => $key]);
            }
        }
        
        return null; // Already at highest tier
    }

    /**
     * Check if testing mode is enabled
     */
    public static function isTestingMode(): bool
    {
        return (bool) config('referral.testing.enabled', false);
    }

    /**
     * Get all configuration as array
     */
    public static function all(): array
    {
        return config('referral', []);
    }
}