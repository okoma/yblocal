<?php

namespace App\Policies;

use App\Models\QuoteResponse;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuoteResponsePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any quote responses.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator() || $user->isBusinessOwner();
    }

    /**
     * Determine if user can view a specific quote response.
     */
    public function view(User $user, QuoteResponse $quoteResponse): bool
    {
        // Admins can view all
        if ($user->isAdmin() || $user->isModerator()) {
            return true;
        }

        // Business owner can view their own responses
        if ($user->isBusinessOwner() && $quoteResponse->business->user_id === $user->id) {
            return true;
        }

        // Quote request owner can view responses to their request
        if ($quoteResponse->quoteRequest->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can create quote responses.
     */
    public function create(User $user): bool
    {
        return $user->isBusinessOwner() && !$user->is_banned && $user->is_active;
    }

    /**
     * Determine if user can update a quote response.
     */
    public function update(User $user, QuoteResponse $quoteResponse): bool
    {
        // Admins can update any
        if ($user->isAdmin()) {
            return true;
        }

        // Business owner can update their own response if still submitted (not accepted/rejected)
        return $user->isBusinessOwner() 
            && $quoteResponse->business->user_id === $user->id
            && $quoteResponse->status === 'submitted';
    }

    /**
     * Determine if user can delete a quote response.
     */
    public function delete(User $user, QuoteResponse $quoteResponse): bool
    {
        // Admins can delete any
        if ($user->isAdmin()) {
            return true;
        }

        // Business owner can delete their own response if still submitted
        return $user->isBusinessOwner() 
            && $quoteResponse->business->user_id === $user->id
            && $quoteResponse->status === 'submitted';
    }
}
