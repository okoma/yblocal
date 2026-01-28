<?php

namespace App\Filament\Customer\Resources\QuoteRequestResource\Pages;

use App\Filament\Customer\Resources\QuoteRequestResource;
use App\Models\Notification;
use App\Notifications\NewQuoteRequestNotification;
use App\Services\QuoteDistributionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreateQuoteRequest extends CreateRecord
{
    protected static string $resource = QuoteRequestResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        $data['status'] = 'open';
        
        // Set default expiration (30 days from now) if not provided
        if (empty($data['expires_at'])) {
            $data['expires_at'] = now()->addDays(30);
        }
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        // Notify eligible businesses about the new quote request
        try {
            $quoteRequest = $this->record;
            $distributionService = app(QuoteDistributionService::class);
            $eligibleBusinesses = $distributionService->getEligibleBusinesses($quoteRequest);
            
            foreach ($eligibleBusinesses as $business) {
                // Get business owner
                $owner = $business->user;
                if ($owner) {
                    $preferences = $owner->preferences;
                    
                    // Send email/in-app notification if enabled
                    if ($preferences && $preferences->notify_new_quote_requests) {
                        // Use Laravel notification for email support
                        $owner->notify(new NewQuoteRequestNotification($quoteRequest));
                    }
                    
                    // Send WhatsApp notification if enabled and verified
                    if ($preferences && 
                        $preferences->notify_new_quote_requests_whatsapp && 
                        $preferences->whatsapp_verified && 
                        !empty($preferences->whatsapp_number)) {
                        
                        try {
                            // TODO: Implement WhatsApp API integration
                            // Recommended services:
                            // 1. Twilio WhatsApp API: https://www.twilio.com/whatsapp
                            // 2. WhatsApp Business API: https://business.whatsapp.com/
                            // 3. Africa's Talking (for African markets): https://africastalking.com/
                            //
                            // Example implementation (using Twilio):
                            // $twilioSid = config('services.twilio.sid');
                            // $twilioToken = config('services.twilio.token');
                            // $twilioWhatsappNumber = config('services.twilio.whatsapp_number');
                            // 
                            // $client = new Twilio\Rest\Client($twilioSid, $twilioToken);
                            // $client->messages->create(
                            //     "whatsapp:" . $preferences->whatsapp_number,
                            //     [
                            //         'from' => "whatsapp:" . $twilioWhatsappNumber,
                            //         'body' => "🎯 New Quote Request Available!\n\n" .
                            //                  "A new quote request '{$quoteRequest->title}' matches your business.\n\n" .
                            //                  "Category: {$quoteRequest->category->name}\n" .
                            //                  "Location: " . ($quoteRequest->cityLocation?->name ?? $quoteRequest->stateLocation?->name) . "\n\n" .
                            //                  "View and submit your quote: " . url(\App\Filament\Business\Pages\AvailableQuoteRequests::getUrl())
                            //     ]
                            // );
                            
                            // For now, log the WhatsApp notification
                            Log::info('WhatsApp quote request notification (pending API integration)', [
                                'user_id' => $owner->id,
                                'whatsapp_number' => $preferences->whatsapp_number,
                                'quote_request_id' => $quoteRequest->id,
                                'business_id' => $business->id,
                                'message' => "New quote request '{$quoteRequest->title}' matches your business",
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Failed to send WhatsApp quote request notification', [
                                'user_id' => $owner->id,
                                'quote_request_id' => $quoteRequest->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
            
            Log::info('Quote request notifications sent', [
                'quote_request_id' => $quoteRequest->id,
                'businesses_notified' => $eligibleBusinesses->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send quote request notifications', [
                'quote_request_id' => $this->record->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
