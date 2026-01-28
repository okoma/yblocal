<?php

namespace App\Observers;

use App\Models\BusinessClaim;
use App\Notifications\ClaimApprovedNotification;
use App\Notifications\ClaimRejectedNotification;
use App\Notifications\ClaimSubmittedNotification;
use Illuminate\Support\Facades\Log;

class BusinessClaimObserver
{
    /**
     * Handle the BusinessClaim "created" event.
     */
    public function created(BusinessClaim $claim): void
    {
        // Send notification when claim is submitted
        if ($claim->user) {
            try {
                $claim->user->notify(new ClaimSubmittedNotification($claim));
                
                Log::info('Claim submitted notification sent', [
                    'claim_id' => $claim->id,
                    'user_id' => $claim->user_id,
                    'business_id' => $claim->business_id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send claim submitted notification', [
                    'claim_id' => $claim->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the BusinessClaim "updated" event.
     */
    public function updated(BusinessClaim $claim): void
    {
        // Check if status changed
        if ($claim->isDirty('status') && $claim->user) {
            $this->handleStatusChange($claim);
        }
    }

    /**
     * Handle claim status changes
     */
    protected function handleStatusChange(BusinessClaim $claim): void
    {
        $oldStatus = $claim->getOriginal('status');
        $newStatus = $claim->status;
        
        try {
            // Claim approved
            if ($oldStatus !== 'approved' && $newStatus === 'approved') {
                $claim->user->notify(new ClaimApprovedNotification($claim));
                Log::info('Claim approved notification sent', [
                    'claim_id' => $claim->id,
                    'user_id' => $claim->user_id,
                ]);
            }
            
            // Claim rejected
            if ($newStatus === 'rejected') {
                $reason = $claim->rejection_reason ?? null;
                $claim->user->notify(new ClaimRejectedNotification($claim, $reason));
                Log::info('Claim rejected notification sent', [
                    'claim_id' => $claim->id,
                    'user_id' => $claim->user_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send claim status notification', [
                'claim_id' => $claim->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
