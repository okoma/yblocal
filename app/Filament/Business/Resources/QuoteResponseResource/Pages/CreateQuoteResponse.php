<?php

namespace App\Filament\Business\Resources\QuoteResponseResource\Pages;

use App\Filament\Business\Resources\QuoteResponseResource;
use App\Models\QuoteRequest;
use App\Models\Wallet;
use App\Services\ActiveBusiness;
use App\Services\QuoteDistributionService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class CreateQuoteResponse extends CreateRecord
{
    protected static string $resource = QuoteResponseResource::class;
    
    public ?QuoteRequest $selectedRequest = null;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $businessId = app(ActiveBusiness::class)->getActiveBusinessId();
        
        if (!$businessId) {
            throw new \Exception('No active business selected');
        }
        
        $data['business_id'] = $businessId;
        $data['status'] = 'submitted';
        
        return $data;
    }
    
    protected function beforeCreate(): void
    {
        $businessId = app(ActiveBusiness::class)->getActiveBusinessId();
        $wallet = Wallet::where('business_id', $businessId)->first();
        
        if (!$wallet || !$wallet->hasQuoteCredits()) {
            Notification::make()
                ->danger()
                ->title('Insufficient Quote Credits')
                ->body('You need at least 1 quote credit to submit a quote. Please purchase quote credits first.')
                ->send();
            
            $this->halt();
        }
        
        // Check if already submitted
        $quoteRequestId = $this->data['quote_request_id'];
        $existing = \App\Models\QuoteResponse::where('quote_request_id', $quoteRequestId)
            ->where('business_id', $businessId)
            ->exists();
        
        if ($existing) {
            Notification::make()
                ->danger()
                ->title('Already Submitted')
                ->body('You have already submitted a quote for this request.')
                ->send();
            
            $this->halt();
        }
    }
    
    protected function afterCreate(): void
    {
        $businessId = app(ActiveBusiness::class)->getActiveBusinessId();
        $wallet = Wallet::where('business_id', $businessId)->first();
        
        // Deduct quote credit
        try {
            DB::beginTransaction();
            
            $wallet->useQuoteCredit(
                "Quote submission for request: {$this->record->quoteRequest->title}",
                $this->record
            );
            
            DB::commit();
            
            Notification::make()
                ->success()
                ->title('Quote Submitted!')
                ->body('Your quote has been submitted successfully. 1 quote credit deducted.')
                ->send();
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Failed to deduct quote credit: ' . $e->getMessage())
                ->send();
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
}
