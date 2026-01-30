<?php

namespace App\Services;

use App\Models\Business;
use App\Services\ReferralConfig;
use App\Models\BusinessReferral;
use App\Models\BusinessReferralCreditTransaction;
use App\Models\CustomerReferral;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Handles referral sign-up: creating CustomerReferral or BusinessReferral
 * when a new business is created with a ref code, and awarding business referral credits.
 */
class ReferralSignupService
{
    public function __construct(protected FraudDetectionService $fraudDetection)
    {
    }
    /**
     * Store referral code in session (call when user lands on register/signup with ?ref=CODE).
     */
    public function storeReferralCodeInSession(?string $code): void
    {
        if ($code === null || $code === '') {
            return;
        }
        Session::put('referral_code', trim($code));
    }

    /**
     * Process referral for a newly created business.
     * Reads session('referral_code'), creates CustomerReferral or BusinessReferral,
     * awards business referral credits when applicable, then clears session.
     */
    public function processReferralForNewBusiness(Business $business): void
    {
        $code = Session::get('referral_code');
        Session::forget('referral_code');

        if ($code === null || $code === '') {
            return;
        }

        $code = trim($code);
        $referrer = $this->resolveReferrer($code);

        if (!$referrer) {
            Log::info('ReferralSignupService: No referrer found for code', ['code' => $code]);
            return;
        }

        if ($referrer['type'] === 'customer') {
            $this->createCustomerReferral($referrer['referrer'], $business, $code);
        } else {
            $this->createBusinessReferralAndAwardCredits($referrer['referrer'], $business, $code);
        }
    }

    /**
     * Resolve referrer from code.
     * Business codes start with 'B'; otherwise treat as customer (user) code.
     *
     * @return array{type: 'customer', referrer: User}|array{type: 'business', referrer: Business}|null
     */
    public function resolveReferrer(string $code): ?array
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        // Business referral code (B + 9 chars)
        if (str_starts_with(strtoupper($code), 'B')) {
            $business = Business::where('referral_code', $code)->first();
            if ($business) {
                return ['type' => 'business', 'referrer' => $business];
            }
            return null;
        }

        // Customer referral code (user)
        $user = User::where('referral_code', $code)
            ->where('role', 'customer')
            ->first();
        if ($user) {
            return ['type' => 'customer', 'referrer' => $user];
        }
        return null;
    }

    protected function createCustomerReferral(User $referrer, Business $business, string $code): void
    {
        // Self-referral: user is the business owner
        if ($referrer->id === $business->user_id) {
            Log::info('ReferralSignupService: Skipped customer self-referral', [
                'user_id' => $referrer->id,
                'business_id' => $business->id,
            ]);
            return;
        }

        // Already referred
        if (CustomerReferral::where('referred_business_id', $business->id)->exists()) {
            Log::info('ReferralSignupService: Business already has customer referral', [
                'business_id' => $business->id,
            ]);
            return;
        }

        // Fraud detection
        $ipAddress = request()->ip();
        $deviceFingerprint = request()->header('X-Device-Fingerprint'); // Client should send this
        $userAgent = request()->userAgent();
        
        $fraudCheck = $this->fraudDetection->checkReferralSignup($ipAddress, $deviceFingerprint, $referrer);

        $referral = CustomerReferral::create([
            'referrer_user_id' => $referrer->id,
            'referred_business_id' => $business->id,
            'referral_code' => $code,
            'status' => 'pending',
            'ip_address' => $ipAddress,
            'device_fingerprint' => $deviceFingerprint,
            'user_agent' => $userAgent,
            'is_suspicious' => $fraudCheck['is_suspicious'],
            'fraud_notes' => $fraudCheck['is_suspicious'] ? implode('; ', $fraudCheck['reasons']) : null,
        ]);
        
        // Record metrics for fraud tracking
        $this->fraudDetection->recordReferralMetrics($ipAddress, $deviceFingerprint, $referrer);

        Log::info('ReferralSignupService: CustomerReferral created', [
            'referrer_user_id' => $referrer->id,
            'referred_business_id' => $business->id,
            'is_suspicious' => $fraudCheck['is_suspicious'],
        ]);
    }

    protected function createBusinessReferralAndAwardCredits(Business $referrerBusiness, Business $referredBusiness, string $code): void
    {
        // Self-referral
        if ($referrerBusiness->id === $referredBusiness->id) {
            Log::info('ReferralSignupService: Skipped business self-referral', [
                'business_id' => $referrerBusiness->id,
            ]);
            return;
        }

        // Already referred
        if (BusinessReferral::where('referred_business_id', $referredBusiness->id)->exists()) {
            Log::info('ReferralSignupService: Business already referred', [
                'referred_business_id' => $referredBusiness->id,
            ]);
            return;
        }

        // Fraud detection
        $ipAddress = request()->ip();
        $deviceFingerprint = request()->header('X-Device-Fingerprint');
        $userAgent = request()->userAgent();
        
        $fraudCheck = $this->fraudDetection->checkReferralSignup($ipAddress, $deviceFingerprint, $referrerBusiness);

        $credits = ReferralConfig::businessCreditsPerSignup();
        if ($credits <= 0) {
            return;
        }

        // If suspicious, don't award credits immediately (pending admin review)
        $status = $fraudCheck['is_suspicious'] ? 'pending' : 'credited';
        $creditsAwarded = $fraudCheck['is_suspicious'] ? 0 : $credits;
        
        if (!$fraudCheck['is_suspicious']) {
            $referrerBusiness->increment('referral_credits', $credits);
            $referrerBusiness->refresh();
        }

        $businessReferral = BusinessReferral::create([
            'referrer_business_id' => $referrerBusiness->id,
            'referred_business_id' => $referredBusiness->id,
            'referral_code' => $code,
            'referral_credits_awarded' => $creditsAwarded,
            'status' => $status,
            'ip_address' => $ipAddress,
            'device_fingerprint' => $deviceFingerprint,
            'user_agent' => $userAgent,
            'is_suspicious' => $fraudCheck['is_suspicious'],
            'fraud_notes' => $fraudCheck['is_suspicious'] ? implode('; ', $fraudCheck['reasons']) : null,
        ]);

        // Only create transaction if credits were awarded
        if (!$fraudCheck['is_suspicious']) {
            BusinessReferralCreditTransaction::create([
                'business_id' => $referrerBusiness->id,
                'business_referral_id' => $businessReferral->id,
                'amount' => $credits,
                'type' => BusinessReferralCreditTransaction::TYPE_EARNED,
                'balance_after' => $referrerBusiness->referral_credits,
                'description' => "Referral credit for {$referredBusiness->business_name} sign-up",
                'reference_type' => BusinessReferral::class,
                'reference_id' => $businessReferral->id,
            ]);
        }
        
        // Record metrics for fraud tracking
        $this->fraudDetection->recordReferralMetrics($ipAddress, $deviceFingerprint, $referrerBusiness);

        Log::info('ReferralSignupService: BusinessReferral created', [
            'referrer_business_id' => $referrerBusiness->id,
            'referred_business_id' => $referredBusiness->id,
            'credits' => $creditsAwarded,
            'is_suspicious' => $fraudCheck['is_suspicious'],
            'status' => $status,
        ]);
    }
}
