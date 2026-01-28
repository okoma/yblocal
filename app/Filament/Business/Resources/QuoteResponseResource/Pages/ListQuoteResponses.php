<?php

namespace App\Filament\Business\Resources\QuoteResponseResource\Pages;

use App\Filament\Business\Resources\QuoteResponseResource;
use App\Models\QuoteRequest;
use App\Services\ActiveBusiness;
use App\Services\QuoteDistributionService;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Notifications\Notification;

class ListQuoteResponses extends ListRecords
{
    protected static string $resource = QuoteResponseResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('browse_requests')
                ->label('Browse Available Requests')
                ->icon('heroicon-o-magnifying-glass')
                ->color('primary')
                ->url(fn () => static::getResource()::getUrl('create')),
        ];
    }
}
