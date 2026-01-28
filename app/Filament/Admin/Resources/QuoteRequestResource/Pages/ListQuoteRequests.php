<?php

namespace App\Filament\Admin\Resources\QuoteRequestResource\Pages;

use App\Filament\Admin\Resources\QuoteRequestResource;
use App\Models\QuoteRequest;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListQuoteRequests extends ListRecords
{
    protected static string $resource = QuoteRequestResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(QuoteRequest::count()),
            
            'open' => Tab::make('Open')
                ->badge(QuoteRequest::where('status', 'open')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'open')),
            
            'accepted' => Tab::make('Accepted')
                ->badge(QuoteRequest::where('status', 'accepted')->count())
                ->badgeColor('primary')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'accepted')),
            
            'closed' => Tab::make('Closed')
                ->badge(QuoteRequest::where('status', 'closed')->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'closed')),
            
            'expired' => Tab::make('Expired')
                ->badge(QuoteRequest::where('status', 'expired')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'expired')),
            
            'today' => Tab::make('Today')
                ->badge(QuoteRequest::whereDate('created_at', today())->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today())),
        ];
    }
}
