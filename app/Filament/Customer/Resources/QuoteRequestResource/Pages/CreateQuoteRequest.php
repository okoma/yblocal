<?php

namespace App\Filament\Customer\Resources\QuoteRequestResource\Pages;

use App\Filament\Customer\Resources\QuoteRequestResource;
use App\Models\Notification;
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
                    
                    // Check if user wants to receive quote request notifications
                    if ($preferences && $preferences->notify_new_quote_requests) {
                        Notification::send(
                            userId: $owner->id,
                            type: 'new_quote_request',
                            title: 'New Quote Request Available',
                            message: "A new quote request '{$quoteRequest->title}' matches your business category and location.",
                            actionUrl: \App\Filament\Business\Pages\AvailableQuoteRequests::getUrl(),
                            extraData: [
                                'quote_request_id' => $quoteRequest->id,
                                'business_id' => $business->id,
                            ]
                        );
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
