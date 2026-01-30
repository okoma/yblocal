<?php

namespace App\Policies;

use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuoteRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any quote requests.
     */
    public function viewAny(User $user): bool
    {
        // Admins, business owners, and customers can view quote requests
        return $user->isAdmin() 
            || $user->isModerator() 
            || $user->isBusinessOwner() 
            || $user->isCustomer();
    }

    /**
     * Determine if user can view a specific quote request.
     */
    public function view(User $user, QuoteRequest $quoteRequest): bool
    {
        // Admins can view all
        if ($user->isAdmin() || $user->isModerator()) {
            return true;
        }

        // Customers can view their own quote requests
        if ($user->isCustomer() && $quoteRequest->user_id === $user->id) {
            return true;
        }

        // Business owners can view quote requests in their category/location
        // OR if they've already submitted a response
        if ($user->isBusinessOwner()) {
            // Check if user has any business that can respond to this quote
            $canRespond = $user->businesses()
                ->where('is_active', true)
                ->where('is_claimed', true)
                ->where(function ($query) use ($quoteRequest) {
                    $query->whereHas('categories', function ($q) use ($quoteRequest) {
                        $q->where('categories.id', $quoteRequest->category_id);
                    })
                    ->orWhere('city_location_id', $quoteRequest->location_id)
                    ->orWhere('state_location_id', $quoteRequest->location_id);
                })
                ->exists();

            if ($canRespond) {
                return true;
            }

            // Or if they've already submitted a quote response
            return $quoteRequest->responses()
                ->whereHas('business', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->exists();
        }

        return false;
    }

    /**
     * Determine if user can create quote requests.
     */
    public function create(User $user): bool
    {
        // Only customers and business owners (for their own needs) can create
        return ($user->isCustomer() || $user->isBusinessOwner()) 
            && !$user->is_banned 
            && $user->is_active;
    }

    /**
     * Determine if user can update a quote request.
     */
    public function update(User $user, QuoteRequest $quoteRequest): bool
    {
        // Admins can update any
        if ($user->isAdmin()) {
            return true;
        }

        // Users can update their own quote requests if still open
        return $quoteRequest->user_id === $user->id 
            && $quoteRequest->status === 'open';
    }

    /**
     * Determine if user can delete a quote request.
     */
    public function delete(User $user, QuoteRequest $quoteRequest): bool
    {
        // Admins can delete any
        if ($user->isAdmin()) {
            return true;
        }

        // Users can delete their own quote requests if no responses yet
        return $quoteRequest->user_id === $user->id 
            && $quoteRequest->responses()->count() === 0;
    }

    /**
     * Determine if user can close a quote request.
     */
    public function close(User $user, QuoteRequest $quoteRequest): bool
    {
        // Admins can close any
        if ($user->isAdmin()) {
            return true;
        }

        // Users can close their own quote requests
        return $quoteRequest->user_id === $user->id 
            && $quoteRequest->status === 'open';
    }

    /**
     * Determine if user can respond to a quote request.
     */
    public function respond(User $user, QuoteRequest $quoteRequest): bool
    {
        // Must be business owner
        if (!$user->isBusinessOwner()) {
            return false;
        }

        // Quote must be open
        if ($quoteRequest->status !== 'open' || $quoteRequest->expires_at < now()) {
            return false;
        }

        // Check if user has a business that matches criteria
        return $user->businesses()
            ->where('is_active', true)
            ->where('is_claimed', true)
            ->where(function ($query) use ($quoteRequest) {
                $query->whereHas('categories', function ($q) use ($quoteRequest) {
                    $q->where('categories.id', $quoteRequest->category_id);
                })
                ->where(function ($q) use ($quoteRequest) {
                    $q->where('city_location_id', $quoteRequest->location_id)
                      ->orWhere('state_location_id', $quoteRequest->location_id);
                });
            })
            ->exists();
    }

    /**
     * Determine if user can shortlist quote responses.
     */
    public function shortlist(User $user, QuoteRequest $quoteRequest): bool
    {
        // Only the quote request owner can shortlist
        return $quoteRequest->user_id === $user->id;
    }

    /**
     * Determine if user can accept a quote.
     */
    public function acceptQuote(User $user, QuoteRequest $quoteRequest): bool
    {
        // Only the quote request owner can accept quotes
        return $quoteRequest->user_id === $user->id 
            && $quoteRequest->status === 'open';
    }
}
