<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BusinessPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any businesses.
     */
    public function viewAny(User $user): bool
    {
        // Admins and moderators can view all
        return $user->isAdmin() || $user->isModerator();
    }

    /**
     * Determine if user can view a specific business.
     */
    public function view(User $user, Business $business): bool
    {
        // Admins can view all
        if ($user->isAdmin() || $user->isModerator()) {
            return true;
        }

        // Business owners can view their own businesses
        if ($user->isBusinessOwner() && $business->user_id === $user->id) {
            return true;
        }

        // Managers can view businesses they manage
        if ($user->isBusinessManager()) {
            return $business->managers()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Determine if user can create businesses.
     */
    public function create(User $user): bool
    {
        // Only business owners can create businesses
        // Customers cannot create businesses (they must switch role)
        return $user->isBusinessOwner() && !$user->is_banned && $user->is_active;
    }

    /**
     * Determine if user can update a business.
     */
    public function update(User $user, Business $business): bool
    {
        // Admins can update any business
        if ($user->isAdmin()) {
            return true;
        }

        // Owner can update their own business
        if ($user->isBusinessOwner() && $business->user_id === $user->id) {
            return true;
        }

        // Managers can update if they have edit permissions
        if ($user->isBusinessManager()) {
            $manager = $business->managers()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            return $manager && ($manager->permissions['edit_business'] ?? false);
        }

        return false;
    }

    /**
     * Determine if user can delete a business.
     */
    public function delete(User $user, Business $business): bool
    {
        // Only admins and business owners can delete
        if ($user->isAdmin()) {
            return true;
        }

        // Owner can delete their own business
        return $user->isBusinessOwner() && $business->user_id === $user->id;
    }

    /**
     * Determine if user can restore a deleted business.
     */
    public function restore(User $user, Business $business): bool
    {
        // Only admins can restore
        return $user->isAdmin();
    }

    /**
     * Determine if user can permanently delete a business.
     */
    public function forceDelete(User $user, Business $business): bool
    {
        // Only admins can force delete
        return $user->isAdmin();
    }

    /**
     * Determine if user can manage business verification.
     */
    public function manageVerification(User $user, Business $business): bool
    {
        // Only admins and moderators
        return $user->isAdmin() || $user->isModerator();
    }

    /**
     * Determine if user can view business analytics.
     */
    public function viewAnalytics(User $user, Business $business): bool
    {
        // Admins can view all analytics
        if ($user->isAdmin() || $user->isModerator()) {
            return true;
        }

        // Owner can view their own analytics
        if ($user->isBusinessOwner() && $business->user_id === $user->id) {
            return true;
        }

        // Managers can view if they have analytics permission
        if ($user->isBusinessManager()) {
            $manager = $business->managers()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            return $manager && ($manager->permissions['view_analytics'] ?? false);
        }

        return false;
    }

    /**
     * Determine if user can manage business subscription.
     */
    public function manageSubscription(User $user, Business $business): bool
    {
        // Only admins and business owners can manage subscriptions
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isBusinessOwner() && $business->user_id === $user->id;
    }

    /**
     * Determine if user can invite managers.
     */
    public function inviteManagers(User $user, Business $business): bool
    {
        // Only admins and business owners
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isBusinessOwner() && $business->user_id === $user->id;
    }
}
