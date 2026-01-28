<?php

namespace App\Filament\Business\Widgets;

use App\Models\QuoteRequest;
use App\Services\ActiveBusiness;
use App\Services\QuoteDistributionService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AvailableQuoteRequestsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $businessId = app(ActiveBusiness::class)->getActiveBusinessId();
        
        if (!$businessId) {
            return $table->query(QuoteRequest::whereRaw('1 = 0'));
        }
        
        $distributionService = app(QuoteDistributionService::class);
        $business = \App\Models\Business::find($businessId);
        
        if (!$business) {
            return $table->query(QuoteRequest::whereRaw('1 = 0'));
        }
        
        $availableRequests = $distributionService->getAvailableQuoteRequests($business);
        $requestIds = $availableRequests->pluck('id')->toArray();
        
        return $table
            ->query(QuoteRequest::query()->whereIn('id', $requestIds))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Request')
                    ->searchable()
                    ->weight('bold')
                    ->limit(40),
                
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('stateLocation.name')
                    ->label('Location')
                    ->formatStateUsing(function ($state, $record) {
                        $location = $state;
                        if ($record->cityLocation) {
                            $location .= ' - ' . $record->cityLocation->name;
                        }
                        return $location;
                    }),
                
                Tables\Columns\TextColumn::make('responses_count')
                    ->label('Quotes')
                    ->counts('responses')
                    ->badge()
                    ->color('success'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Posted')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->heading('Available Quote Requests')
            ->description('Quote requests matching your business category and location')
            ->emptyStateHeading('No available quote requests')
            ->emptyStateDescription('New quote requests matching your business will appear here')
            ->emptyStateIcon('heroicon-o-document-text')
            ->paginated([5, 10])
            ->defaultActions([
                Tables\Actions\Action::make('view_all')
                    ->label('View All & Submit Quotes')
                    ->icon('heroicon-o-arrow-right')
                    ->url(fn () => \App\Filament\Business\Pages\AvailableQuoteRequests::getUrl())
                    ->color('primary'),
            ]);
    }
}
