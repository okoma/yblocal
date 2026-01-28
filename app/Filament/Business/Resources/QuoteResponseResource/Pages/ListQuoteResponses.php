<?php

namespace App\Filament\Business\Resources\QuoteResponseResource\Pages;

use App\Filament\Business\Resources\QuoteResponseResource;
use App\Models\QuoteRequest;
use App\Models\Wallet;
use App\Services\ActiveBusiness;
use App\Services\QuoteDistributionService;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class ListQuoteResponses extends ListRecords
{
    protected static string $resource = QuoteResponseResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('browse_available')
                ->label('Browse Available Requests')
                ->icon('heroicon-o-magnifying-glass')
                ->color('primary')
                ->url(fn () => \App\Filament\Business\Pages\AvailableQuoteRequests::getUrl()),
        ];
    }
}

