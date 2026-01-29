<?php

namespace App\Filament\Business\Pages;

use App\Models\QuoteResponse;
use App\Services\ActiveBusiness;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class WonQuotes extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'Won Quotes';

    protected static ?string $navigationGroup = 'Quotes';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.business.pages.won-quotes';

    public function getTitle(): string
    {
        return 'Won Quotes';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getQuery())
            ->columns([
                Tables\Columns\TextColumn::make('quoteRequest.title')
                    ->label('Quote Request')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('quoteRequest.user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('NGN')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('delivery_time')
                    ->label('Delivery Time')
                    ->icon('heroicon-o-clock'),
                Tables\Columns\TextColumn::make('message')
                    ->label('Message')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->message),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Won')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('No won quotes yet')
            ->emptyStateDescription('Quotes you submit will appear here once a customer accepts them.')
            ->emptyStateIcon('heroicon-o-trophy');
    }

    protected function getQuery(): Builder
    {
        $businessId = app(ActiveBusiness::class)->getActiveBusinessId();
        if (!$businessId) {
            return QuoteResponse::query()->whereRaw('1 = 0');
        }
        return QuoteResponse::query()
            ->where('business_id', $businessId)
            ->where('status', 'accepted')
            ->with(['quoteRequest.user', 'quoteRequest.category']);
    }

    public static function getNavigationBadge(): ?string
    {
        $businessId = app(ActiveBusiness::class)->getActiveBusinessId();
        if (!$businessId) {
            return null;
        }
        $count = QuoteResponse::where('business_id', $businessId)
            ->where('status', 'accepted')
            ->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
