<?php

namespace App\Filament\Customer\Resources\QuoteRequestResource\Pages;

use App\Filament\Customer\Resources\QuoteRequestResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

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
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
