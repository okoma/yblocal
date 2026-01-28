<?php

namespace App\Http\Controllers;

use App\Models\BusinessType;
use App\Models\Business;
use App\Models\BusinessClick;
use App\Models\Category;
use App\Models\Location;
use App\Enums\PageType;  // ← ADD THIS
use Illuminate\Http\Request;

class BusinessTypeController extends Controller
{
    /**
     * Display businesses by business type
     * Records impressions for each visible business
     */
    public function show(Request $request, $businessTypeSlug)
    {
        $pageType = PageType::ARCHIVE;  // ← CHANGE: Use enum instead of string
        $referralSource = BusinessClick::detectReferralSource();
        
        // Get business type
        $businessType = BusinessType::where('slug', $businessTypeSlug)
            ->where('is_active', true)
            ->firstOrFail();
        
        // Get businesses of this type
        $query = Business::query()
            ->where('status', 'active')
            ->whereHas('businessType', function ($q) use ($businessTypeSlug) {
                $q->where('slug', $businessTypeSlug);
            })
            ->with(['businessType', 'stateLocation', 'cityLocation', 'categories']);
        
        // Filter by state location
        if ($request->has('state') && $request->state) {
            $query->where(function ($q) use ($request) {
                $q->where('state', 'like', "%{$request->state}%")
                  ->orWhereHas('stateLocation', function ($q2) use ($request) {
                      $q2->where('slug', $request->state);
                  });
            });
        }
        
        // Filter by city location
        if ($request->has('city') && $request->city) {
            $query->where(function ($q) use ($request) {
                $q->where('city', 'like', "%{$request->city}%")
                  ->orWhereHas('cityLocation', function ($q2) use ($request) {
                      $q2->where('slug', $request->city);
                  });
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
        $categories = Category::where('business_type_id', $businessType->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $states = Location::where('type', 'state')->where('is_active', true)->orderBy('name')->get();
        
        return view('business-types.show', compact('businesses', 'businessType', 'categories', 'states'));
    }

    /**
     * Get all business types (AJAX endpoint)
     */
    public function index()
    {
        $businessTypes = BusinessType::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'icon']);
        
        return response()->json($businessTypes);
    }
}