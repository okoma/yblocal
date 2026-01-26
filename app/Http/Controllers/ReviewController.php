<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Review;
use App\Http\Requests\StoreReviewRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * Get reviews for a business (paginated)
     * 
     * @param Request $request
     * @param string $businessType Business type slug
     * @param string $slug Business slug
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request, string $businessType, string $slug)
    {
        $business = Business::where('slug', $slug)
            ->where('status', 'active')
            ->whereHas('businessType', function ($query) use ($businessType) {
                $query->where('slug', $businessType);
            })
            ->firstOrFail();

        // Get sort parameter
        $sort = $request->get('sort', 'newest'); // newest, highest, lowest, helpful
        
        // Build query for approved and published reviews
        $query = $business->reviews()
            ->where('is_approved', true)
            ->whereNotNull('published_at')
            ->with(['user:id,name,avatar'])
            ->orderBy('published_at', 'desc');

        // Apply sorting
        switch ($sort) {
            case 'highest':
                $query->orderBy('rating', 'desc');
                break;
            case 'lowest':
                $query->orderBy('rating', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('published_at', 'desc');
                break;
        }

        // If AJAX request, return JSON
        if ($request->wantsJson() || $request->ajax()) {
            $reviews = $query->paginate(10);
            
            return response()->json([
                'success' => true,
                'reviews' => $reviews->items(),
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ],
                'summary' => [
                    'total_reviews' => $business->total_reviews ?? 0,
                    'avg_rating' => $business->avg_rating ?? 0,
                ],
            ]);
        }

        // For regular requests, return view (for iframe display)
        $reviews = $query->paginate(10);
        
        return view('businesses.reviews', compact('business', 'reviews', 'sort'));
    }

    /**
     * Submit a new review for a business
     * 
     * @param StoreReviewRequest $request
     * @param string $businessType Business type slug
     * @param string $slug Business slug
     * @return JsonResponse
     */
    public function store(StoreReviewRequest $request, string $businessType, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)
            ->where('status', 'active')
            ->whereHas('businessType', function ($query) use ($businessType) {
                $query->where('slug', $businessType);
            })
            ->firstOrFail();

        // Validation is handled by StoreReviewRequest

        // Check if user already reviewed this business
        $userId = Auth::id();
        if ($userId) {
            $existingReview = Review::where('reviewable_type', Business::class)
                ->where('reviewable_id', $business->id)
                ->where('user_id', $userId)
                ->first();

            if ($existingReview) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already reviewed this business. You can edit your existing review.',
                ], 422);
            }
        }

        // Handle photo uploads
        $photos = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('review-photos', 'public');
                $photos[] = $path;
            }
        }

        // Create review
        $review = Review::create([
            'reviewable_type' => Business::class,
            'reviewable_id' => $business->id,
            'user_id' => $userId,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'photos' => !empty($photos) ? $photos : null,
            'is_verified_purchase' => false, // Can be enhanced later
            'is_approved' => true, // Auto-approve for now, can add moderation later
            'published_at' => now(),
        ]);

        // Update business aggregate stats
        $business->updateAggregateStats();

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully!',
            'review' => $review->load('user:id,name,avatar'),
        ], 201);
    }

    /**
     * Vote on a review (helpful/not helpful)
     * Optional feature - can be implemented later
     * 
     * @param int $reviewId
     * @return JsonResponse
     */
    public function vote(Request $request, int $reviewId): JsonResponse
    {
        $review = Review::findOrFail($reviewId);
        
        $validator = Validator::make($request->all(), [
            'helpful' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid vote',
                'errors' => $validator->errors(),
            ], 422);
        }

        // TODO: Implement review voting system if needed
        // This would require a review_votes table
        
        return response()->json([
            'success' => true,
            'message' => 'Vote recorded',
        ]);
    }
}
