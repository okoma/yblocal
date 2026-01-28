<?php

namespace App\Filament\Customer\Resources\QuoteRequestResource\Pages;

use App\Filament\Customer\Resources\QuoteRequestResource;
use Filament\Resources\Pages\EditRecord;

class EditQuoteRequest extends EditRecord
{
    protected static string $resource = QuoteRequestResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
