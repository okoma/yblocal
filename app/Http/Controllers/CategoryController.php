<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Business;
use App\Models\BusinessClick;
use App\Models\Location;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display businesses by category
     * Records impressions for each visible business
     */
    public function show(Request $request, $categorySlug)
    {
        $pageType = 'category';
        $referralSource = BusinessClick::detectReferralSource();
        
        // Get category
        $category = Category::where('slug', $categorySlug)
            ->where('is_active', true)
            ->with('businessType')
            ->firstOrFail();
        
        // Get businesses in this category
        $query = Business::query()
            ->where('status', 'active')
            ->whereHas('categories', function ($query) use ($categorySlug) {
                $query->where('slug', $categorySlug);
            })
            ->with(['businessType', 'stateLocation', 'cityLocation']);
        
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
        $states = Location::where('type', 'state')->where('is_active', true)->orderBy('name')->get();
        
        return view('categories.show', compact('businesses', 'category', 'states'));
    }
}
