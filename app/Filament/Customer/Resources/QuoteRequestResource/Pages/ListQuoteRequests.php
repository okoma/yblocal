<?php

namespace App\Filament\Customer\Resources\QuoteRequestResource\Pages;

use App\Filament\Customer\Resources\QuoteRequestResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListQuoteRequests extends ListRecords
{
    protected static string $resource = QuoteRequestResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Request a Quote')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
