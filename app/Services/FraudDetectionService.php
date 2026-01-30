<?php

namespace App\Services;

use App\Models\BusinessReferral;
use App\Models\CustomerReferral;
use App\Models\User;
use App\Models\Business;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Detects fraudulent referral patterns and suspicious activity.
 */
class FraudDetectionService
{
    // Thresholds for fraud detection
    protected const MAX_REFERRALS_PER_IP_PER_DAY = 3;
    protected const MAX_REFERRALS_PER_DEVICE_PER_DAY = 3;
    protected const MAX_REFERRALS_PER_USER_PER_MONTH = 20;
    protected const SUSPICIOUS_IP_PREFIX_LIMIT = 5; // Max referrals from same /24 subnet
    
    /**
     * Check if a referral signup is suspicious based on IP, device, and pattern analysis.
     *
     * @param string|null $ipAddress
     * @param string|null $deviceFingerprint
     * @param User|Business $referrer (could be User for customer referrals, or Business for business referrals)
     * @return array{is_suspicious: bool, reasons: array}
     */
    public function checkReferralSignup(
        ?string $ipAddress,
        ?string $deviceFingerprint,
        User|Business $referrer
    ): array {
        $reasons = [];
        
        // Check IP-based fraud
        if ($ipAddress) {
            $ipReasons = $this->checkIpFraud($ipAddress);
            $reasons = array_merge($reasons, $ipReasons);
        }
        
        // Check device-based fraud
        if ($deviceFingerprint) {
            $deviceReasons = $this->checkDeviceFraud($deviceFingerprint);
            $reasons = array_merge($reasons, $deviceReasons);
        }
        
        // Check referrer volume fraud
        $volumeReasons = $this->checkReferrerVolume($referrer);
        $reasons = array_merge($reasons, $volumeReasons);
        
        // Check for same IP as referrer
        if ($referrer instanceof User && $ipAddress) {
            if ($this->isSameIpAsReferrer($referrer, $ipAddress)) {
                $reasons[] = 'Same IP address as referrer (potential self-referral)';
            }
        }
        
        $isSuspicious = count($reasons) > 0;
        
        if ($isSuspicious) {
            Log::warning('FraudDetectionService: Suspicious referral detected', [
                'ip' => $ipAddress,
                'device' => $deviceFingerprint,
                'referrer_type' => get_class($referrer),
                'referrer_id' => $referrer->id,
                'reasons' => $reasons,
            ]);
        }
        
        return [
            'is_suspicious' => $isSuspicious,
            'reasons' => $reasons,
        ];
    }
    
    /**
     * Check for IP-based fraud patterns.
     */
    protected function checkIpFraud(string $ipAddress): array
    {
        $reasons = [];
        
        // Check referrals from this exact IP today
        $todayKey = 'fraud:ip:' . $ipAddress . ':' . now()->format('Y-m-d');
        $ipCount = Cache::get($todayKey, 0);
        
        if ($ipCount >= self::MAX_REFERRALS_PER_IP_PER_DAY) {
            $reasons[] = "Exceeded max referrals from IP ($ipCount referrals today)";
        }
        
        // Check subnet (/24) for distributed fraud
        $subnet = $this->getSubnet($ipAddress);
        if ($subnet) {
            $subnetKey = 'fraud:subnet:' . $subnet . ':' . now()->format('Y-m-d');
            $subnetCount = Cache::get($subnetKey, 0);
            
            if ($subnetCount >= self::SUSPICIOUS_IP_PREFIX_LIMIT) {
                $reasons[] = "Suspicious activity from subnet $subnet ($subnetCount referrals today)";
            }
        }
        
        return $reasons;
    }
    
    /**
     * Check for device-based fraud patterns.
     */
    protected function checkDeviceFraud(string $deviceFingerprint): array
    {
        $reasons = [];
        
        $todayKey = 'fraud:device:' . $deviceFingerprint . ':' . now()->format('Y-m-d');
        $deviceCount = Cache::get($todayKey, 0);
        
        if ($deviceCount >= self::MAX_REFERRALS_PER_DEVICE_PER_DAY) {
            $reasons[] = "Exceeded max referrals from device ($deviceCount referrals today)";
        }
        
        return $reasons;
    }
    
    /**
     * Check if referrer is creating too many referrals.
     */
    protected function checkReferrerVolume(User|Business $referrer): array
    {
        $reasons = [];
        
        $monthStart = now()->startOfMonth();
        
        if ($referrer instanceof User) {
            // Customer referrals
            $count = CustomerReferral::where('referrer_user_id', $referrer->id)
                ->where('created_at', '>=', $monthStart)
                ->count();
        } else {
            // Business referrals
            $count = BusinessReferral::where('referrer_business_id', $referrer->id)
                ->where('created_at', '>=', $monthStart)
                ->count();
        }
        
        if ($count >= self::MAX_REFERRALS_PER_USER_PER_MONTH) {
            $reasons[] = "Referrer exceeded monthly limit ($count referrals this month)";
        }
        
        return $reasons;
    }
    
    /**
     * Check if signup IP matches referrer's last known IP.
     */
    protected function isSameIpAsReferrer(User $referrer, string $ipAddress): bool
    {
        // Check if referrer has used this IP recently
        $referrerIpKey = 'user:last_ip:' . $referrer->id;
        $referrerLastIp = Cache::get($referrerIpKey);
        
        return $referrerLastIp && $referrerLastIp === $ipAddress;
    }
    
    /**
     * Record tracking data for fraud detection (call after each referral signup).
     */
    public function recordReferralMetrics(
        ?string $ipAddress,
        ?string $deviceFingerprint,
        User|Business $referrer
    ): void {
        $today = now()->format('Y-m-d');
        
        // Increment IP counter (expires in 24 hours)
        if ($ipAddress) {
            $ipKey = 'fraud:ip:' . $ipAddress . ':' . $today;
            Cache::increment($ipKey, 1);
            Cache::put($ipKey, Cache::get($ipKey, 1), now()->addDay());
            
            // Increment subnet counter
            $subnet = $this->getSubnet($ipAddress);
            if ($subnet) {
                $subnetKey = 'fraud:subnet:' . $subnet . ':' . $today;
                Cache::increment($subnetKey, 1);
                Cache::put($subnetKey, Cache::get($subnetKey, 1), now()->addDay());
            }
        }
        
        // Increment device counter (expires in 24 hours)
        if ($deviceFingerprint) {
            $deviceKey = 'fraud:device:' . $deviceFingerprint . ':' . $today;
            Cache::increment($deviceKey, 1);
            Cache::put($deviceKey, Cache::get($deviceKey, 1), now()->addDay());
        }
    }
    
    /**
     * Mark a referral as suspicious.
     */
    public function markAsSuspicious(BusinessReferral|CustomerReferral $referral, array $reasons): void
    {
        $referral->update([
            'is_suspicious' => true,
            'fraud_notes' => implode('; ', $reasons),
        ]);
        
        Log::warning('Referral marked as suspicious', [
            'referral_type' => get_class($referral),
            'referral_id' => $referral->id,
            'reasons' => $reasons,
        ]);
    }
    
    /**
     * Verify a suspicious referral as legitimate (admin action).
     */
    public function verifyReferral(BusinessReferral|CustomerReferral $referral): void
    {
        $referral->update([
            'is_suspicious' => false,
            'verified_at' => now(),
            'fraud_notes' => ($referral->fraud_notes ?? '') . ' [Verified by admin at ' . now()->toDateTimeString() . ']',
        ]);
        
        Log::info('Suspicious referral verified by admin', [
            'referral_type' => get_class($referral),
            'referral_id' => $referral->id,
        ]);
    }
    
    /**
     * Get /24 subnet from IP address (e.g., "192.168.1.0/24").
     */
    protected function getSubnet(string $ipAddress): ?string
    {
        // IPv4 only for now
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ipAddress);
            if (count($parts) === 4) {
                return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0/24';
            }
        }
        
        return null;
    }
    
    /**
     * Get all suspicious referrals for admin review.
     */
    public function getSuspiciousReferrals(string $type = 'all'): array
    {
        $results = [];
        
        if ($type === 'all' || $type === 'business') {
            $results['business'] = BusinessReferral::where('is_suspicious', true)
                ->whereNull('verified_at')
                ->with(['referrerBusiness', 'referredBusiness'])
                ->get();
        }
        
        if ($type === 'all' || $type === 'customer') {
            $results['customer'] = CustomerReferral::where('is_suspicious', true)
                ->whereNull('verified_at')
                ->with(['referrerUser', 'referredBusiness'])
                ->get();
        }
        
        return $results;
    }
}
