<?php

namespace App\Observers;

use App\Models\Lead;
use App\Notifications\InquiryResponseNotification;
use App\Notifications\NewLeadNotification;
use Illuminate\Support\Facades\Log;

class LeadObserver
{
    /**
     * Handle the Lead "created" event.
     * Send notification to business owner about new lead.
     */
    public function created(Lead $lead): void
    {
        // Send notification to business owner
        if ($lead->business && $lead->business->user) {
            try {
                $lead->business->user->notify(new NewLeadNotification($lead));
                
                Log::info('New lead notification sent to business owner', [
                    'lead_id' => $lead->id,
                    'business_id' => $lead->business_id,
                    'owner_id' => $lead->business->user_id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send new lead notification', [
                    'lead_id' => $lead->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the Lead "updated" event.
     * Send notification when business owner responds to inquiry.
     */
    public function updated(Lead $lead): void
    {
        // Check if reply was just added or updated
        if ($lead->isDirty('reply_message') && $lead->is_replied && !empty($lead->reply_message)) {
            // Set replied_at timestamp if not already set
            if (!$lead->replied_at) {
                $lead->replied_at = now();
                $lead->saveQuietly(); // Save without triggering events again
            }
            
            // Send notification to customer who submitted the lead
            if ($lead->user) {
                try {
                    $lead->user->notify(new InquiryResponseNotification($lead));
                    
                    Log::info('Inquiry response notification sent', [
                        'lead_id' => $lead->id,
                        'customer_id' => $lead->user_id,
                        'business_id' => $lead->business_id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send inquiry response notification', [
                        'lead_id' => $lead->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
