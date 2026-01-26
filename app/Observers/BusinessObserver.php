<?php

namespace App\Observers;

use App\Models\Business;
use App\Notifications\VerificationApprovedNotification;
use App\Notifications\VerificationRejectedNotification;
use App\Notifications\VerificationSubmittedNotification;
use Illuminate\Support\Facades\Log;

class BusinessObserver
{
    /**
     * Handle the Business "updated" event.
     */
    public function updated(Business $business): void
    {
        // Check if verification status changed
        if ($business->isDirty('verification_status')) {
            $this->handleVerificationStatusChange($business);
        }
    }

    /**
     * Handle verification status changes
     */
    protected function handleVerificationStatusChange(Business $business): void
    {
        $oldStatus = $business->getOriginal('verification_status');
        $newStatus = $business->verification_status;
        
        // Skip if no owner
        if (!$business->user) {
            return;
        }
        
        try {
            // Verification submitted
            if ($oldStatus !== 'pending' && $newStatus === 'pending') {
                $business->user->notify(new VerificationSubmittedNotification($business));
                Log::info('Verification submitted notification sent', [
                    'business_id' => $business->id,
                    'owner_id' => $business->user_id,
                ]);
            }
            
            // Verification approved
            if ($oldStatus !== 'verified' && $newStatus === 'verified') {
                $business->user->notify(new VerificationApprovedNotification($business));
                Log::info('Verification approved notification sent', [
                    'business_id' => $business->id,
                    'owner_id' => $business->user_id,
                ]);
            }
            
            // Verification rejected
            if ($newStatus === 'rejected') {
                $reason = $business->verification_notes ?? null;
                $business->user->notify(new VerificationRejectedNotification($business, $reason));
                Log::info('Verification rejected notification sent', [
                    'business_id' => $business->id,
                    'owner_id' => $business->user_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send verification status notification', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
