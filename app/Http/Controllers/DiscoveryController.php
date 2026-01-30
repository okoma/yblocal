<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Category;
use App\Models\Location;
use App\Models\BusinessClick;
use App\Enums\PageType;  // ← ADD THIS
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class DiscoveryController extends Controller
{
    /**
     * Unified business discovery for all listing pages
     * Handles: search, category browsing, location browsing, filtering, sorting
     * 
     * Also handles clean URLs:
     * - /lagos (location)
     * - /hotels (category or business type)
     * - /lagos/hotels (location + category/business type)
     * 
     * @param Request $request
     * @param string|null $locationOrCategory First URL segment (location, category, or business type)
     * @param string|null $category Second URL segment (category or business type)
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request, ?string $locationOrCategory = null, ?string $category = null)
    {
        // ============================================
        // DETECT URL PARAMETERS FROM CLEAN URLS
        // ============================================
        
        // Handle clean URL structure: /lagos, /hotels, /lagos/hotels
        if ($locationOrCategory) {
            // Check if first segment is a location
            $location = Location::where('slug', $locationOrCategory)
                ->where('is_active', true)
                ->first();
            
            if ($location) {
                // It's a location
                if ($location->type === 'state') {
                    $request->merge(['state' => $locationOrCategory]);
                } elseif ($location->type === 'city') {
                    $request->merge(['city' => $locationOrCategory]);
                }
                
                // If there's a second segment, it's a category or business type
                if ($category) {
                    // Check if it's a category
                    $categoryModel = Category::where('slug', $category)
                        ->where('is_active', true)
                        ->first();
                    
                    if ($categoryModel) {
                        $request->merge(['category' => $category]);
                    } else {
                        // Check if it's a business type
                        $businessTypeModel = BusinessType::where('slug', $category)
                            ->where('is_active', true)
                            ->first();
                        
                        if ($businessTypeModel) {
                            $request->merge(['business_type' => $category]);
                        }
                    }
                }
            } else {
                // Not a location, check if it's a category or business type
                $categoryModel = Category::where('slug', $locationOrCategory)
                    ->where('is_active', true)
                    ->first();
                
                if ($categoryModel) {
                    $request->merge(['category' => $locationOrCategory]);
                } else {
                    // Check if it's a business type
                    $businessTypeModel = BusinessType::where('slug', $locationOrCategory)
                        ->where('is_active', true)
                        ->first();
                    
                    if ($businessTypeModel) {
                        $request->merge(['business_type' => $locationOrCategory]);
                    }
                }
            }
        }
        
        // Detect page type and referral source
        $pageType = $this->detectPageType($request);
        $referralSource = BusinessClick::detectReferralSource();
        
        // Build base query with relationships
        $query = Business::query()
            ->where('status', 'active')
            ->with([
                'businessType:id,name,slug,icon',
                'stateLocation:id,name,slug',
                'cityLocation:id,name,slug,parent_id',
                'categories:id,name,slug,icon,color'
            ]);
        
        // ============================================
        // FILTERS
        // ============================================
        
        // Keyword Search
        if ($request->filled('q')) {
            $searchQuery = $request->q;
            $query->where(function ($q) use ($searchQuery) {
                $q->where('business_name', 'like', "%{$searchQuery}%")
                  ->orWhere('description', 'like', "%{$searchQuery}%")
                  ->orWhere('city', 'like', "%{$searchQuery}%")
                  ->orWhere('state', 'like', "%{$searchQuery}%")
                  ->orWhere('area', 'like', "%{$searchQuery}%")
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
        
        // State Location Filter
        if ($request->filled('state')) {
            $query->where(function ($q) use ($request) {
                $q->where('state', 'like', "%{$request->state}%")
                  ->orWhereHas('stateLocation', function ($q2) use ($request) {
                      $q2->where('slug', $request->state);
                  });
            });
        }
        
        // City Location Filter
        if ($request->filled('city')) {
            $query->where(function ($q) use ($request) {
                $q->where('city', 'like', "%{$request->city}%")
                  ->orWhereHas('cityLocation', function ($q2) use ($request) {
                      $q2->where('slug', $request->city);
                  });
            });
        }
        
        // Rating Filter (minimum rating)
        if ($request->filled('rating')) {
            $minRating = (float) $request->rating;
            $query->where('avg_rating', '>=', $minRating);
        }
        
        // Verified Businesses Only
        if ($request->boolean('verified')) {
            $query->where('is_verified', true);
        }
        
        // Premium Businesses Only
        if ($request->boolean('premium')) {
            $query->premium();
        }
        
        // Open Now Filter
        if ($request->boolean('open_now')) {
            $query->whereNotNull('business_hours');
            // Note: Filtering by open now requires checking business hours
            // This is a simplified version - you may need to enhance this
        }
        
        // ============================================
        // SORTING
        // ============================================
        
        $sort = $request->get('sort', 'relevance');
        
        switch ($sort) {
            case 'rating':
                // Highest rated first
                $query->orderBy('avg_rating', 'desc')
                      ->orderBy('total_reviews', 'desc');
                break;
                
            case 'reviews':
                // Most reviewed first
                $query->orderBy('total_reviews', 'desc')
                      ->orderBy('avg_rating', 'desc');
                break;
                
            case 'newest':
                // Recently added businesses
                $query->latest('created_at');
                break;
                
            case 'name':
                // Alphabetical
                $query->orderBy('business_name', 'asc');
                break;
                
            case 'distance':
                // Distance-based (requires lat/lng in request)
                if ($request->filled(['lat', 'lng'])) {
                    $lat = (float) $request->lat;
                    $lng = (float) $request->lng;
                    $radius = (float) $request->get('radius', 50); // km default radius for bounding box
                    $earthRadius = 6371; // Earth's radius in km

                    // Calculate an approximate bounding box around the point (cheap prefilter)
                    $deltaLat = rad2deg($radius / $earthRadius);
                    $deltaLng = rad2deg($radius / $earthRadius / max(cos(deg2rad($lat)), 0.00001));

                    $minLat = $lat - $deltaLat;
                    $maxLat = $lat + $deltaLat;
                    $minLng = $lng - $deltaLng;
                    $maxLng = $lng + $deltaLng;

                    // Apply bounding-box prefilter, then compute exact Haversine distance and order by it
                    $query->withinBoundingBox($minLat, $maxLat, $minLng, $maxLng)
                          ->selectRaw(
                              "*, ({$earthRadius} * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance",
                              [$lat, $lng, $lat]
                          )
                          ->orderBy('distance', 'asc');
                } else {
                    // Fallback to relevance if no coordinates
                    $query->latest('created_at');
                }
                break;
                
            case 'relevance':
            default:
                // Sponsored first, then premium, then verified, then by rating
                $query->orderByRaw('is_premium DESC')
                      ->orderByRaw('is_verified DESC')
                      ->orderBy('avg_rating', 'desc')
                      ->orderBy('total_reviews', 'desc');
                break;
        }
        
        // Pagination
        $perPage = $request->get('per_page', 20);
        $businesses = $query->paginate($perPage)->withQueryString();
        
        // Record impressions for each visible business
        foreach ($businesses as $business) {
            try {
                $business->recordImpression($pageType, $referralSource);
            } catch (\Exception $e) {
                \Log::warning("Failed to record impression for business {$business->id}: " . $e->getMessage());
            }
        }
        
        // Prepare context data for view
        $context = $this->prepareContext($request);
        
        // If AJAX request, return JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'businesses' => $businesses->items(),
                'pagination' => [
                    'current_page' => $businesses->currentPage(),
                    'last_page' => $businesses->lastPage(),
                    'per_page' => $businesses->perPage(),
                    'total' => $businesses->total(),
                ],
                'context' => $context,
            ]);
        }
        
        // Add SEO metadata
        $seoData = $this->prepareSeoData($request, $context);
        $context = array_merge($context, $seoData);
        
        // Return unified view with Livewire component
        return view('businesses.discovery', $context);
    }
    
    /**
     * Prepare SEO metadata for the page
     */
    private function prepareSeoData(Request $request, array $context): array
    {
        $seo = [
            'pageTitle' => 'Discover Local Businesses',
            'metaDescription' => 'Find and connect with verified local businesses in your area.',
        ];
        
        if ($request->filled('q')) {
            $query = $request->get('q');
            $seo['pageTitle'] = "Search: {$query} - Find Businesses";
            $seo['metaDescription'] = "Search results for {$query}. Find verified local businesses, read reviews, and connect with quality service providers.";
        } elseif (isset($context['state'])) {
            $state = $context['state'];
            $seo['pageTitle'] = "Businesses in {$state->name} - Local Directory";
            $seo['metaDescription'] = "Discover trusted businesses in {$state->name}. Browse verified listings, read reviews, and find the best local services.";
        } elseif (isset($context['businessType'])) {
            $businessType = $context['businessType'];
            $seo['pageTitle'] = "{$businessType->name} - Browse by Type";
            $seo['metaDescription'] = "Browse {$businessType->name} businesses. Verified listings, customer reviews, and detailed information to help you choose.";
        } elseif (isset($context['categories']) && $context['categories']->isNotEmpty()) {
            $category = $context['categories']->first();
            $seo['pageTitle'] = "{$category->name} - Browse by Category";
            $seo['metaDescription'] = "Explore {$category->name} businesses. Find verified providers, compare services, and read authentic customer reviews.";
        }
        
        return $seo;
    }
    
    /**
     * Detect page type from request
     */
    private function detectPageType(Request $request): PageType  // ← CHANGE: Return PageType enum
    {
        $path = $request->path();
        
        if ($request->filled('q')) {
            return PageType::SEARCH;  // ← CHANGE: Return enum
        }
        
        if ($request->filled('category')) {
            return PageType::CATEGORY;  // ← CHANGE: Return enum
        }
        
        if ($request->filled('state') || $request->filled('city')) {
            return PageType::LOCATION;  // ← CHANGE: Return enum (assuming you have LOCATION in your enum)
        }
        
        if ($request->filled('business_type')) {
            return PageType::BUSINESS_TYPE;  // ← CHANGE: Return enum (assuming you have BUSINESS_TYPE in your enum)
        }
        
        return PageType::ARCHIVE;  // ← CHANGE: Return enum
    }
    
    /**
     * Prepare context data (filter options, active filters, etc.)
     */
    private function prepareContext(Request $request): array
    {
        $context = [];
        
        // Get filter options
        $context['businessTypes'] = BusinessType::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'icon']);
        
        $context['states'] = Location::where('type', 'state')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
        
        // Get categories (filtered by business type if applicable)
        $categoriesQuery = Category::where('is_active', true);
        if ($request->filled('business_type')) {
            $businessType = BusinessType::where('slug', $request->business_type)->first();
            if ($businessType) {
                $categoriesQuery->where('business_type_id', $businessType->id);
                $context['businessType'] = $businessType;
            }
        }
        $context['categories'] = $categoriesQuery->orderBy('name')->get(['id', 'name', 'slug', 'icon', 'color']);
        
        // Get cities (filtered by state if applicable)
        if ($request->filled('state')) {
            $state = Location::where('slug', $request->state)->where('type', 'state')->first();
            if ($state) {
                $context['state'] = $state;
                $context['cities'] = Location::where('parent_id', $state->id)
                    ->where('type', 'city')
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'slug']);
            }
        }
        
        // Active filters
        $context['activeFilters'] = [
            'q' => $request->get('q'),
            'business_type' => $request->get('business_type'),
            'category' => $request->get('category'),
            'state' => $request->get('state'),
            'city' => $request->get('city'),
            'rating' => $request->get('rating'),
            'verified' => $request->boolean('verified'),
            'premium' => $request->boolean('premium'),
            'open_now' => $request->boolean('open_now'),
            'sort' => $request->get('sort', 'relevance'),
        ];
        
        // Search query if present
        if ($request->filled('q')) {
            $context['searchQuery'] = $request->get('q');
        }
        
        return $context;
    }
    
    /**
     * Determine which view to render based on request
     */
    private function getViewName(Request $request): string
    {
        if ($request->filled('q')) {
            return 'businesses.search';
        }
        
        if ($request->filled('category')) {
            return 'categories.show';
        }
        
        if ($request->filled('state') || $request->filled('city')) {
            return 'locations.show';
        }
        
        if ($request->filled('business_type')) {
            return 'business-types.show';
        }
        
        return 'businesses.index';
    }
}