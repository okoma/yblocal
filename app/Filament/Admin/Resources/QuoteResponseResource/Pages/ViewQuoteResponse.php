<?php

namespace App\Filament\Admin\Resources\QuoteResponseResource\Pages;

use App\Filament\Admin\Resources\QuoteResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQuoteResponse extends ViewRecord
{
    protected static string $resource = QuoteResponseResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
