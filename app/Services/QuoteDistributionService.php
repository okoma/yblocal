<?php

// ============================================
// app/Services/QuoteDistributionService.php
// Handles distribution of quote requests to eligible businesses
// ============================================

namespace App\Services;

use App\Models\QuoteRequest;
use App\Models\Business;
use App\Models\Wallet;
use App\Services\ActiveBusiness;
use Illuminate\Support\Facades\Log;

class QuoteDistributionService
{
    /**
     * Get eligible businesses for a quote request
     * 
     * @param QuoteRequest $quoteRequest
     * @param int $limit Maximum number of businesses to return (default: 10)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEligibleBusinesses(QuoteRequest $quoteRequest, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $query = Business::query()
            ->where('status', 'active')
            ->where('is_verified', true);
        
        // Category match: Business must have this category
        $query->whereHas('categories', function ($q) use ($quoteRequest) {
            $q->where('categories.id', $quoteRequest->category_id);
        });
        
        // Location match: City takes priority if set, otherwise use state
        $query->where(function ($q) use ($quoteRequest) {
            if ($quoteRequest->city_location_id) {
                // If city is selected: match businesses in that specific city only
                $q->where('city_location_id', $quoteRequest->city_location_id);
            } else {
                // If only state is selected: match businesses in that state
                $q->where('state_location_id', $quoteRequest->state_location_id)
                    ->orWhereIn('city_location_id', function ($subQuery) use ($quoteRequest) {
                        // Include all cities that are children of this state
                        $subQuery->select('id')
                            ->from('locations')
                            ->where('parent_id', $quoteRequest->state_location_id)
                            ->where('type', 'city');
                    });
            }
        });
        
        // Must have quote credits available (either in wallet or via plan)
        $query->where(function ($q) {
            $q->whereHas('wallet', function ($walletQuery) {
                $walletQuery->where('quote_credits', '>', 0);
            })
            ->orWhereHas('activeSubscription', function ($subQuery) {
                // Check if subscription plan includes quote credits
                // This can be extended based on plan features
                $subQuery->whereHas('plan', function ($planQuery) {
                    $planQuery->where('slug', '!=', 'free'); // Free plans might not have quote credits
                });
            });
        });
        
        // Exclude businesses that already responded
        $query->whereDoesntHave('responses', function ($q) use ($quoteRequest) {
            $q->where('quote_request_id', $quoteRequest->id);
        });
        
        // Limit results
        return $query->limit($limit)->get();
    }
    
    /**
     * Check if a business is eligible for a quote request
     * 
     * @param Business $business
     * @param QuoteRequest $quoteRequest
     * @return array ['eligible' => bool, 'reason' => string|null]
     */
    public function checkEligibility(Business $business, QuoteRequest $quoteRequest): array
    {
        // Check if business is active and verified
        if ($business->status !== 'active' || !$business->is_verified) {
            return ['eligible' => false, 'reason' => 'Business is not active or verified'];
        }
        
        // Check category match
        if (!$business->categories()->where('categories.id', $quoteRequest->category_id)->exists()) {
            return ['eligible' => false, 'reason' => 'Category mismatch'];
        }
        
        // Check location match: City takes priority if set, otherwise use state
        $locationMatch = false;
        
        if ($quoteRequest->city_location_id) {
            // If city is selected: must match that specific city
            $locationMatch = $business->city_location_id === $quoteRequest->city_location_id;
        } else {
            // If only state is selected: check if business is in that state
            $locationMatch = $business->state_location_id === $quoteRequest->state_location_id
                || ($business->city_location_id && \App\Models\Location::where('id', $business->city_location_id)
                    ->where('parent_id', $quoteRequest->state_location_id)
                    ->where('type', 'city')
                    ->exists());
        }
        
        if (!$locationMatch) {
            return ['eligible' => false, 'reason' => 'Location mismatch'];
        }
        
        // Check if already responded
        if ($business->quoteResponses()->where('quote_request_id', $quoteRequest->id)->exists()) {
            return ['eligible' => false, 'reason' => 'Already submitted a quote'];
        }
        
        // Check quote credits (for actual submission, not viewing)
        $wallet = $business->wallet;
        if (!$wallet || $wallet->quote_credits < 1) {
            return ['eligible' => false, 'reason' => 'Insufficient quote credits'];
        }
        
        return ['eligible' => true, 'reason' => null];
    }
    
    /**
     * Get available quote requests for a business
     * Businesses can VIEW all matching requests regardless of credits
     * Credit check happens during quote submission
     * 
     * @param Business $business
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableQuoteRequests(Business $business): \Illuminate\Database\Eloquent\Collection
    {
        $query = QuoteRequest::query()
            ->where('status', 'open')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
        
        // Category match
        $categoryIds = $business->categories()->pluck('categories.id');
        $query->whereIn('category_id', $categoryIds);
        
        // Location match: City takes priority if set, otherwise use state
        $query->where(function ($q) use ($business) {
            // Match if quote has city selected and it matches business's city
            $q->where(function ($cityQuery) use ($business) {
                $cityQuery->whereNotNull('city_location_id')
                    ->where('city_location_id', $business->city_location_id);
            });
            
            // Match if quote has only state selected and business is in that state
            $q->orWhere(function ($stateQuery) use ($business) {
                $stateQuery->whereNull('city_location_id')
                    ->where('state_location_id', $business->state_location_id);
            });
            
            // Match if quote has only state selected and business is in a city within that state
            if ($business->city_location_id) {
                $q->orWhere(function ($stateCityQuery) use ($business) {
                    $stateCityQuery->whereNull('city_location_id')
                        ->whereIn('state_location_id', function ($subQuery) use ($business) {
                            $subQuery->select('parent_id')
                                ->from('locations')
                                ->where('id', $business->city_location_id)
                                ->where('type', 'city');
                        });
                });
            }
        });
        
        // Exclude already responded
        $query->whereDoesntHave('responses', function ($q) use ($business) {
            $q->where('business_id', $business->id);
        });
        
        // REMOVED: Credit check - businesses can see all available requests
        // Credit check happens during quote submission instead
        
        return $query->with(['category', 'stateLocation', 'cityLocation', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}