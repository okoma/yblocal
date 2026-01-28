<?php

namespace App\Http\Controllers;

use App\Models\BusinessType;
use App\Models\Category;
use App\Models\Location;
use App\Models\Amenity;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FilterController extends Controller
{
    /**
     * Get filter metadata for frontend use
     * Returns categories, locations, rating options, amenities, etc.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $businessTypeId = $request->get('business_type_id');
        
        // Get active business types
        $businessTypes = BusinessType::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'icon']);

        // Get categories (optionally filtered by business type)
        $categoriesQuery = Category::where('is_active', true);
        if ($businessTypeId) {
            $categoriesQuery->where('business_type_id', $businessTypeId);
        }
        $categories = $categoriesQuery->orderBy('name')
            ->get(['id', 'business_type_id', 'name', 'slug', 'icon', 'color']);

        // Get locations (states and cities)
        $states = Location::where('type', 'state')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $cities = Location::where('type', 'city')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'slug']);

        // Get amenities
        $amenities = Amenity::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'icon']);

        // Get payment methods
        $paymentMethods = PaymentMethod::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'icon']);

        // Rating options
        $ratingOptions = [
            ['value' => 5, 'label' => '5 Stars'],
            ['value' => 4, 'label' => '4+ Stars'],
            ['value' => 3, 'label' => '3+ Stars'],
            ['value' => 2, 'label' => '2+ Stars'],
            ['value' => 1, 'label' => '1+ Stars'],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'business_types' => $businessTypes,
                'categories' => $categories,
                'locations' => [
                    'states' => $states,
                    'cities' => $cities,
                ],
                'amenities' => $amenities,
                'payment_methods' => $paymentMethods,
                'rating_options' => $ratingOptions,
                'sort_options' => [
                    ['value' => 'relevance', 'label' => 'Most Relevant'],
                    ['value' => 'rating', 'label' => 'Highest Rated'],
                    ['value' => 'newest', 'label' => 'Newest'],
                    ['value' => 'name', 'label' => 'Name (A-Z)'],
                ],
            ],
        ]);
    }

    /**
     * Get cities by state (AJAX endpoint)
     * 
     * @param string $stateSlug
     * @return JsonResponse
     */
    public function getCitiesByState(string $stateSlug): JsonResponse
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

        return response()->json([
            'success' => true,
            'cities' => $cities,
        ]);
    }
}
