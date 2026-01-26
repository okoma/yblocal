<?php

namespace App\Observers;

use App\Models\Review;
use App\Notifications\ReviewReplyNotification;
use Illuminate\Support\Facades\Log;

class ReviewObserver
{
    /**
     * Handle the Review "updated" event.
     * Send notification when business owner replies to a review.
     */
    public function updated(Review $review): void
    {
        // Check if reply was just added or updated
        if ($review->isDirty('reply') && !empty($review->reply)) {
            // Set replied_at timestamp if not already set
            if (!$review->replied_at) {
                $review->replied_at = now();
                $review->saveQuietly(); // Save without triggering events again
            }
            
            // Send notification to customer who wrote the review
            if ($review->user) {
                try {
                    $review->user->notify(new ReviewReplyNotification($review));
                    
                    Log::info('Review reply notification sent', [
                        'review_id' => $review->id,
                        'customer_id' => $review->user_id,
                        'business_id' => $review->reviewable_id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send review reply notification', [
                        'review_id' => $review->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Handle the Review "created" event.
     * Send notification to business owner about new review.
     */
    public function created(Review $review): void
    {
        // Send notification to business owner
        $business = $review->reviewable;
        
        if ($business && $business->user) {
            try {
                $business->user->notify(new \App\Notifications\NewReviewNotification($review));
                
                Log::info('New review notification sent to business owner', [
                    'review_id' => $review->id,
                    'business_id' => $business->id,
                    'owner_id' => $business->user_id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send new review notification', [
                    'review_id' => $review->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
