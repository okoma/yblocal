<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessClick;
use App\Models\Review;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    /**
     * Display single business profile page
     * Records click (cookie-based) and view
     * 
     * Responsibilities:
     * - Load business core details
     * - Load category and location context
     * - Provide rating summary
     * - Load services and products
     * - Expose contact actions
     * 
     * @param Request $request
     * @param string $businessType Business type slug (e.g., 'hotel', 'restaurant')
     * @param string $slug Business slug
     * @return \Illuminate\View\View
     */
    public function show(Request $request, string $businessType, string $slug)
    {
        // Find business with matching slug and business type
        $business = Business::where('slug', $slug)
            ->where('status', 'active')
            ->whereHas('businessType', function ($query) use ($businessType) {
                $query->where('slug', $businessType);
            })
            ->with([
                'businessType:id,name,slug,icon',
                'stateLocation:id,name,slug',
                'cityLocation:id,name,slug,parent_id',
                'categories:id,name,slug,icon,color',
                'products' => function ($query) {
                    $query->where('is_available', true)->ordered();
                },
                'socialAccounts' => function ($query) {
                    $query->where('is_active', true);
                },
                'officials' => function ($query) {
                    $query->where('is_active', true)->ordered();
                },
                'faqs' => function ($query) {
                    $query->where('is_active', true)->ordered();
                },
                'paymentMethods:id,name,slug,icon',
                'amenities:id,name,slug,icon',
                'owner:id,name,avatar'
            ])
            ->firstOrFail();
        
        // Auto-detect referral source and page type using model methods
        $referralSource = BusinessClick::detectReferralSource();
        $sourcePageType = BusinessClick::detectSourcePageType();
        
        // Record click (cookie-based, one per person)
        try {
            $business->recordClick($referralSource, $sourcePageType);
        } catch (\Exception $e) {
            \Log::warning("Failed to record click for business {$business->id}: " . $e->getMessage());
        }
        
        // Record view (always counts)
        try {
            $business->recordView($referralSource);
        } catch (\Exception $e) {
            \Log::warning("Failed to record view for business {$business->id}: " . $e->getMessage());
        }
        
        // Get rating summary
        $ratingSummary = [
            'avg_rating' => $business->avg_rating ?? 0,
            'total_reviews' => $business->total_reviews ?? 0,
            'rating_breakdown' => $this->getRatingBreakdown($business->id),
        ];
        
        // Check if business is currently open
        $isOpen = $business->isOpen();
        
        return view('businesses.show', compact('business', 'ratingSummary', 'isOpen'));
    }
    
    /**
     * Get rating breakdown (count of each star rating)
     * 
     * @param int $businessId
     * @return array
     */
    private function getRatingBreakdown(int $businessId): array
    {
        $reviews = Review::where('reviewable_type', Business::class)
            ->where('reviewable_id', $businessId)
            ->where('is_approved', true)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();
        
        // Initialize all ratings
        $breakdown = [
            5 => $reviews[5] ?? 0,
            4 => $reviews[4] ?? 0,
            3 => $reviews[3] ?? 0,
            2 => $reviews[2] ?? 0,
            1 => $reviews[1] ?? 0,
        ];
        
        return $breakdown;
    }
}
