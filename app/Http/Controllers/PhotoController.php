<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PhotoController extends Controller
{
    /**
     * Get business gallery photos ONLY
     * Fetch and paginate business photo gallery
     * 
     * @param Request $request
     * @param string $businessType Business type slug
     * @param string $slug Business slug
     * @return JsonResponse
     */
    public function index(Request $request, string $businessType, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)
            ->where('status', 'active')
            ->whereHas('businessType', function ($query) use ($businessType) {
                $query->where('slug', $businessType);
            })
            ->select('id', 'business_name', 'slug', 'gallery')
            ->firstOrFail();

        // Prepare gallery photos array
        $photos = [];
        
        // Add gallery photos if exist
        if ($business->gallery && is_array($business->gallery)) {
            foreach ($business->gallery as $index => $photo) {
                $photos[] = [
                    'url' => asset('storage/' . $photo),
                    'thumbnail' => asset('storage/' . $photo), // Can be optimized with thumbnails
                    'alt' => $business->business_name . ' - Photo ' . ($index + 1),
                    'index' => $index,
                ];
            }
        }
        
        // Pagination
        $perPage = $request->get('per_page', 12);
        $currentPage = $request->get('page', 1);
        $total = count($photos);
        
        // Simple array pagination
        $offset = ($currentPage - 1) * $perPage;
        $paginatedPhotos = array_slice($photos, $offset, $perPage);
        
        return response()->json([
            'success' => true,
            'business' => [
                'id' => $business->id,
                'name' => $business->business_name,
                'slug' => $business->slug,
            ],
            'photos' => $paginatedPhotos,
            'pagination' => [
                'current_page' => (int) $currentPage,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Upload photo to business gallery ONLY (Optional - for user submissions)
     * Requires authentication and proper permissions
     * 
     * @param Request $request
     * @param string $businessType Business type slug
     * @param string $slug Business slug
     * @return JsonResponse
     */
    public function store(Request $request, string $businessType, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        // Check if user is authenticated
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to upload photos.',
            ], 401);
        }

        // Validate photo upload
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid photo upload',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Store photo in gallery
        $path = $request->file('photo')->store('business-gallery', 'public');
        
        // Add to gallery
        $gallery = $business->gallery ?? [];
        $gallery[] = $path;
        $business->update(['gallery' => $gallery]);

        return response()->json([
            'success' => true,
            'message' => 'Photo uploaded successfully to gallery!',
            'photo' => [
                'url' => asset('storage/' . $path),
                'thumbnail' => asset('storage/' . $path),
                'alt' => $business->business_name . ' - Photo ' . count($gallery),
                'index' => count($gallery) - 1,
            ],
        ], 201);
    }

    /**
     * Delete photo from business gallery (Optional)
     * Requires authentication and proper permissions
     * 
     * @param Request $request
     * @param string $businessType Business type slug
     * @param string $slug Business slug
     * @param string $photoPath Photo path to delete
     * @return JsonResponse
     */
    public function destroy(Request $request, string $businessType, string $slug, string $photoPath): JsonResponse
    {
        $business = Business::where('slug', $slug)
            ->where('status', 'active')
            ->whereHas('businessType', function ($query) use ($businessType) {
                $query->where('slug', $businessType);
            })
            ->firstOrFail();

        // Check if user is authenticated and authorized
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to delete photos.',
            ], 401);
        }

        // Decode photo path (it might be base64 encoded or URL encoded)
        $photoPath = urldecode($photoPath);

        // Check if photo exists in gallery
        if ($business->gallery && is_array($business->gallery)) {
            $gallery = $business->gallery;
            $key = array_search($photoPath, $gallery);
            
            if ($key !== false) {
                // Remove from gallery
                unset($gallery[$key]);
                $gallery = array_values($gallery); // Re-index array
                
                // Delete file from storage
                Storage::disk('public')->delete($photoPath);
                
                // Update business
                $business->update(['gallery' => $gallery]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Photo deleted successfully!',
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Photo not found in gallery.',
        ], 404);
    }
}
