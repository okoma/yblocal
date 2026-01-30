<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubscriptionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any subscriptions.
     */
    public function viewAny(User $user): bool
    {
        // Admins can view all subscriptions
        return $user->isAdmin() || $user->isModerator();
    }

    /**
     * Determine if user can view a specific subscription.
     */
    public function view(User $user, Subscription $subscription): bool
    {
        // Admins can view all
        if ($user->isAdmin() || $user->isModerator()) {
            return true;
        }

        // Business owner can view their business subscriptions
        if ($user->isBusinessOwner() && $subscription->business->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can create subscriptions.
     */
    public function create(User $user): bool
    {
        // Only business owners can create subscriptions
        return $user->isBusinessOwner() && !$user->is_banned && $user->is_active;
    }

    /**
     * Determine if user can update a subscription.
     */
    public function update(User $user, Subscription $subscription): bool
    {
        // Only admins can directly update subscriptions
        // Normal users use upgrade/downgrade actions
        return $user->isAdmin();
    }

    /**
     * Determine if user can cancel a subscription.
     */
    public function cancel(User $user, Subscription $subscription): bool
    {
        // Admins can cancel any subscription
        if ($user->isAdmin()) {
            return true;
        }

        // Business owner can cancel their own active subscription
        return $user->isBusinessOwner() 
            && $subscription->business->user_id === $user->id
            && $subscription->status === 'active';
    }

    /**
     * Determine if user can renew a subscription.
     */
    public function renew(User $user, Subscription $subscription): bool
    {
        // Admins can renew any
        if ($user->isAdmin()) {
            return true;
        }

        // Business owner can renew their expired/expiring subscription
        return $user->isBusinessOwner() 
            && $subscription->business->user_id === $user->id
            && in_array($subscription->status, ['expired', 'expiring', 'active']);
    }

    /**
     * Determine if user can upgrade a subscription.
     */
    public function upgrade(User $user, Subscription $subscription): bool
    {
        // Business owner can upgrade their active subscription
        return $user->isBusinessOwner() 
            && $subscription->business->user_id === $user->id
            && $subscription->status === 'active';
    }

    /**
     * Determine if user can downgrade a subscription.
     */
    public function downgrade(User $user, Subscription $subscription): bool
    {
        // Business owner can downgrade their active subscription
        return $user->isBusinessOwner() 
            && $subscription->business->user_id === $user->id
            && $subscription->status === 'active';
    }

    /**
     * Determine if user can delete a subscription.
     */
    public function delete(User $user, Subscription $subscription): bool
    {
        // Only admins can delete subscriptions (for cleanup)
        return $user->isAdmin();
    }
}
