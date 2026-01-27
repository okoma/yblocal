<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Business;
use App\Models\BusinessClick;
use App\Models\BusinessType;
use App\Models\Category;
use App\Enums\PageType;  // ← ADD THIS
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    /**
     * Display businesses by location (state/city)
     * Records impressions for each visible business
     */
    public function show(Request $request, $locationSlug)
    {
        $pageType = PageType::LOCATION;  // ← CHANGE: Use enum instead of string
        $referralSource = BusinessClick::detectReferralSource();
        
        // Get location
        $location = Location::where('slug', $locationSlug)
            ->where('is_active', true)
            ->with('parent')
            ->firstOrFail();
        
        // Determine if it's a state or city
        $isState = $location->type === 'state';
        $isCity = $location->type === 'city';
        
        // Build query
        $query = Business::query()
            ->where('status', 'active')
            ->with(['businessType', 'stateLocation', 'cityLocation', 'categories']);
        
        if ($isState) {
            $query->where(function ($q) use ($location) {
                $q->where('state', 'like', "%{$location->name}%")
                  ->orWhereHas('stateLocation', function ($q2) use ($location) {
                      $q2->where('id', $location->id);
                  });
            });
        } elseif ($isCity) {
            $query->where(function ($q) use ($location) {
                $q->where('city', 'like', "%{$location->name}%")
                  ->orWhereHas('cityLocation', function ($q2) use ($location) {
                      $q2->where('id', $location->id);
                  });
            });
        }
        
        // Filter by business type
        if ($request->has('business_type') && $request->business_type) {
            $query->whereHas('businessType', function ($q) use ($request) {
                $q->where('slug', $request->business_type);
            });
        }
        
        // Filter by category
        if ($request->has('category') && $request->category) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }
        
        $businesses = $query->latest()->paginate(20)->withQueryString();
        
        // Record impressions for each visible business
        foreach ($businesses as $business) {
            try {
                $business->recordImpression($pageType, $referralSource);
            } catch (\Exception $e) {
                \Log::warning("Failed to record impression for business {$business->id}: " . $e->getMessage());
            }
        }
        
        // Get filter options
        $businessTypes = BusinessType::where('is_active', true)->orderBy('name')->get();
        
        return view('locations.show', compact('businesses', 'location', 'businessTypes', 'isState', 'isCity'));
    }

    /**
     * Get cities by state (AJAX endpoint)
     */
    public function getCitiesByState(Request $request, $stateSlug): JsonResponse
    {
        $state = Location::where('slug', $stateSlug)
            ->where('type', 'state')
            ->where('is_active', true)
            ->firstOrFail();
        
        $cities = Location::where('parent_id', $state->id)
            ->where('type', 'city')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
        
        return response()->json($cities);
    }

    /**
     * Get all states (AJAX endpoint)
     */
    public function getStates(): JsonResponse
    {
        $states = Location::where('type', 'state')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
        
        return response()->json($states);
    }
}