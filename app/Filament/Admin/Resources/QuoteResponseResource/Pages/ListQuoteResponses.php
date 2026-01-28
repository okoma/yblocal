<?php

namespace App\Filament\Admin\Resources\QuoteResponseResource\Pages;

use App\Filament\Admin\Resources\QuoteResponseResource;
use App\Models\QuoteResponse;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListQuoteResponses extends ListRecords
{
    protected static string $resource = QuoteResponseResource::class;
    
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
                ->badge(QuoteResponse::count()),
            
            'submitted' => Tab::make('Submitted')
                ->badge(QuoteResponse::where('status', 'submitted')->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'submitted')),
            
            'shortlisted' => Tab::make('Shortlisted')
                ->badge(QuoteResponse::where('status', 'shortlisted')->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'shortlisted')),
            
            'accepted' => Tab::make('Accepted')
                ->badge(QuoteResponse::where('status', 'accepted')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'accepted')),
            
            'rejected' => Tab::make('Rejected')
                ->badge(QuoteResponse::where('status', 'rejected')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected')),
            
            'today' => Tab::make('Today')
                ->badge(QuoteResponse::whereDate('created_at', today())->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today())),
        ];
    }
}
