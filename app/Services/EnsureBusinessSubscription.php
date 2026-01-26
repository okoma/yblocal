<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Service to ensure a business always has an active subscription.
 * Automatically assigns a free plan if the business has no active subscription.
 */
class EnsureBusinessSubscription
{
    /**
     * Ensure the business has an active subscription.
     * If not, assign a free plan.
     *
     * @param Business $business
     * @return Subscription|null The active subscription (existing or newly created)
     */
    public function ensure(Business $business): ?Subscription
    {
        // Check if business already has an active subscription
        $activeSubscription = $business->activeSubscription();
        
        if ($activeSubscription) {
            return $activeSubscription;
        }

        // Business has no active subscription - assign free plan
        return $this->assignFreePlan($business);
    }

    /**
     * Assign a free plan subscription to the business.
     *
     * @param Business $business
     * @return Subscription|null
     */
    protected function assignFreePlan(Business $business): ?Subscription
    {
        $freePlan = SubscriptionPlan::where('slug', 'free')
            ->where('is_active', true)
            ->first();

        if (!$freePlan) {
            Log::error('Free plan not found - cannot assign subscription to business', [
                'business_id' => $business->id,
            ]);
            return null;
        }

        $user = Auth::user() ?? $business->user;

        if (!$user) {
            Log::error('No user available to assign subscription', [
                'business_id' => $business->id,
            ]);
            return null;
        }

        // Create free plan subscription
        $subscription = Subscription::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'subscription_plan_id' => $freePlan->id,
            'billing_interval' => 'yearly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
            'auto_renew' => true,
        ]);

        Log::info('Free plan subscription assigned to business', [
            'business_id' => $business->id,
            'subscription_id' => $subscription->id,
            'plan_id' => $freePlan->id,
        ]);

        return $subscription;
    }
}
