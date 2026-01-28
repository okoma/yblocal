<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MapController extends Controller
{
    /**
     * Get businesses for map display
     * Returns lightweight geo data for map pins
     * 
     * Responsibilities:
     * - Return lightweight geo data (lat/lng)
     * - Provide minimal business info for map pins
     * - Support filtering and bounds
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Build base query
        $query = Business::query()
            ->where('status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');
        
        // ============================================
        // FILTERS
        // ============================================
        
        // Search query
        if ($request->filled('q')) {
            $searchQuery = $request->q;
            $query->where(function ($q) use ($searchQuery) {
                $q->where('business_name', 'like', "%{$searchQuery}%")
                  ->orWhere('description', 'like', "%{$searchQuery}%")
                  ->orWhereHas('businessType', function ($q2) use ($searchQuery) {
                      $q2->where('name', 'like', "%{$searchQuery}%");
                  })
                  ->orWhereHas('categories', function ($q2) use ($searchQuery) {
                      $q2->where('name', 'like', "%{$searchQuery}%");
                  });
            });
        }
        
        // Business Type Filter
        if ($request->filled('business_type')) {
            $query->whereHas('businessType', function ($q) use ($request) {
                $q->where('slug', $request->business_type);
            });
        }
        
        // Category Filter
        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }
        
        // Rating Filter
        if ($request->filled('rating')) {
            $minRating = (float) $request->rating;
            $query->where('avg_rating', '>=', $minRating);
        }
        
        // Verified Only
        if ($request->boolean('verified')) {
            $query->where('is_verified', true);
        }
        
        // Premium Only
        if ($request->boolean('premium')) {
            $query->premium();
        }
        
        // ============================================
        // MAP BOUNDS FILTERING
        // ============================================
        
        // Filter by map bounds (viewport)
        if ($request->filled(['bounds_ne_lat', 'bounds_ne_lng', 'bounds_sw_lat', 'bounds_sw_lng'])) {
            $neLat = $request->bounds_ne_lat;
            $neLng = $request->bounds_ne_lng;
            $swLat = $request->bounds_sw_lat;
            $swLng = $request->bounds_sw_lng;
            
            $query->whereBetween('latitude', [$swLat, $neLat])
                  ->whereBetween('longitude', [$swLng, $neLng]);
        }
        
        // ============================================
        // RADIUS FILTERING (Center point + radius)
        // ============================================
        
        if ($request->filled(['center_lat', 'center_lng', 'radius'])) {
            $centerLat = $request->center_lat;
            $centerLng = $request->center_lng;
            $radius = $request->radius; // in kilometers
            
            // Use Haversine formula to filter by radius
            $query->whereRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * sin(radians(latitude))
                )) <= ?
            ", [$centerLat, $centerLng, $centerLat, $radius]);
        }
        
        // ============================================
        // LIMIT RESULTS FOR MAP PERFORMANCE
        // ============================================
        
        // Limit results to prevent map overload
        $limit = min($request->get('limit', 200), 500); // Max 500 pins
        
        // Get businesses with minimal data
        $businesses = $query->select([
                'id',
                'business_name',
                'slug',
                'latitude',
                'longitude',
                'address',
                'city',
                'state',
                'avg_rating',
                'total_reviews',
                'is_verified',
                'is_premium',
                'logo',
            ])
            ->with([
                'businessType:id,name,slug,icon',
                'categories:id,name,slug,icon,color'
            ])
            ->limit($limit)
            ->get();
        
        // Transform to map-friendly format
        $mapData = $businesses->map(function ($business) {
            return [
                'id' => $business->id,
                'name' => $business->business_name,
                'slug' => $business->slug,
                'url' => route('businesses.show', [
                    'businessType' => $business->businessType->slug ?? 'business',
                    'slug' => $business->slug
                ]),
                'position' => [
                    'lat' => (float) $business->latitude,
                    'lng' => (float) $business->longitude,
                ],
                'address' => $business->address,
                'city' => $business->city,
                'state' => $business->state,
                'rating' => [
                    'avg' => (float) ($business->avg_rating ?? 0),
                    'count' => (int) ($business->total_reviews ?? 0),
                ],
                'verified' => (bool) $business->is_verified,
                'premium' => (bool) $business->is_premium,
                'logo' => $business->logo ? asset('storage/' . $business->logo) : null,
                'business_type' => [
                    'name' => $business->businessType->name ?? null,
                    'slug' => $business->businessType->slug ?? null,
                    'icon' => $business->businessType->icon ?? null,
                ],
                'categories' => $business->categories->map(function ($category) {
                    return [
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'icon' => $category->icon,
                        'color' => $category->color,
                    ];
                })->toArray(),
            ];
        });
        
        return response()->json([
            'success' => true,
            'businesses' => $mapData,
            'count' => $mapData->count(),
            'limit_reached' => $mapData->count() >= $limit,
        ]);
    }
    
    /**
     * Get single business location for map
     * Returns detailed info for a specific business marker
     * 
     * @param Request $request
     * @param string $slug Business slug
     * @return JsonResponse
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)
            ->where('status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with([
                'businessType:id,name,slug,icon',
                'categories:id,name,slug,icon,color',
            ])
            ->firstOrFail();
        
        return response()->json([
            'success' => true,
            'business' => [
                'id' => $business->id,
                'name' => $business->business_name,
                'slug' => $business->slug,
                'url' => route('businesses.show', [
                    'businessType' => $business->businessType->slug ?? 'business',
                    'slug' => $business->slug
                ]),
                'position' => [
                    'lat' => (float) $business->latitude,
                    'lng' => (float) $business->longitude,
                ],
                'address' => $business->address,
                'city' => $business->city,
                'state' => $business->state,
                'area' => $business->area,
                'phone' => $business->phone,
                'email' => $business->email,
                'website' => $business->website,
                'rating' => [
                    'avg' => (float) ($business->avg_rating ?? 0),
                    'count' => (int) ($business->total_reviews ?? 0),
                ],
                'verified' => (bool) $business->is_verified,
                'premium' => (bool) $business->is_premium,
                'logo' => $business->logo ? asset('storage/' . $business->logo) : null,
                'cover_photo' => $business->cover_photo ? asset('storage/' . $business->cover_photo) : null,
                'business_type' => [
                    'name' => $business->businessType->name ?? null,
                    'slug' => $business->businessType->slug ?? null,
                    'icon' => $business->businessType->icon ?? null,
                ],
                'categories' => $business->categories->map(function ($category) {
                    return [
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'icon' => $category->icon,
                        'color' => $category->color,
                    ];
                })->toArray(),
                'is_open' => $business->isOpen(),
            ],
        ]);
    }
    
    /**
     * Get nearby businesses based on coordinates
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function nearby(Request $request): JsonResponse
    {
        $validator = \Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.1|max:100', // km
            'limit' => 'nullable|integer|min:1|max:50',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid coordinates',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $lat = $request->lat;
        $lng = $request->lng;
        $radius = $request->get('radius', 5); // Default 5km
        $limit = $request->get('limit', 20);
        
        // Find businesses within radius using Haversine formula
        $businesses = Business::query()
            ->where('status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("
                *,
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * sin(radians(latitude))
                )) AS distance
            ", [$lat, $lng, $lat])
            ->having('distance', '<=', $radius)
            ->orderBy('distance', 'asc')
            ->with([
                'businessType:id,name,slug,icon',
                'categories:id,name,slug,icon,color',
            ])
            ->limit($limit)
            ->get();
        
        $mapData = $businesses->map(function ($business) {
            return [
                'id' => $business->id,
                'name' => $business->business_name,
                'slug' => $business->slug,
                'url' => route('businesses.show', [
                    'businessType' => $business->businessType->slug ?? 'business',
                    'slug' => $business->slug
                ]),
                'position' => [
                    'lat' => (float) $business->latitude,
                    'lng' => (float) $business->longitude,
                ],
                'distance' => round($business->distance, 2), // km
                'address' => $business->address,
                'city' => $business->city,
                'rating' => [
                    'avg' => (float) ($business->avg_rating ?? 0),
                    'count' => (int) ($business->total_reviews ?? 0),
                ],
                'verified' => (bool) $business->is_verified,
                'premium' => (bool) $business->is_premium,
                'logo' => $business->logo ? asset('storage/' . $business->logo) : null,
                'business_type' => [
                    'name' => $business->businessType->name ?? null,
                    'icon' => $business->businessType->icon ?? null,
                ],
            ];
        });
        
        return response()->json([
            'success' => true,
            'businesses' => $mapData,
            'count' => $mapData->count(),
            'search_params' => [
                'lat' => (float) $lat,
                'lng' => (float) $lng,
                'radius' => (float) $radius,
            ],
        ]);
    }
}
