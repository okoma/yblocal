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
                    
                    // Send Telegram notification if enabled
                    $preferences = $lead->user->preferences;
                    if ($preferences && 
                        $preferences->notify_inquiry_response_received_telegram && 
                        $preferences->telegram_notifications &&
                        $preferences->getTelegramIdentifier()) {
                        
                        try {
                            // TODO: Implement Telegram API integration
                            // Recommended services:
                            // 1. Telegram Bot API: https://core.telegram.org/bots/api
                            // 2. Laravel Telegram Bot: https://github.com/irazasyed/telegram-bot-sdk
                            //
                            // Example implementation:
                            // $telegram = app('telegram');
                            // $telegram->sendMessage([
                            //     'chat_id' => $preferences->getTelegramIdentifier(),
                            //     'text' => "📧 Inquiry Response\n\n" .
                            //              "{$lead->business->business_name} responded to your inquiry:\n\n" .
                            //              "\"{$lead->reply_message}\"\n\n" .
                            //              "View: " . url('/customer/my-inquiries/' . $lead->id)
                            // ]);
                            
                            Log::info('Telegram inquiry response notification (pending API integration)', [
                                'user_id' => $lead->user_id,
                                'lead_id' => $lead->id,
                                'telegram_id' => $preferences->getTelegramIdentifier(),
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Failed to send Telegram inquiry response notification', [
                                'user_id' => $lead->user_id,
                                'lead_id' => $lead->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                    
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
