<?php

namespace App\Filament\Customer\Resources\QuoteRequestResource\Pages;

use App\Filament\Customer\Resources\QuoteRequestResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Actions;

class ViewQuoteRequest extends ViewRecord
{
    protected static string $resource = QuoteRequestResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => $record->status === 'open'),
            Actions\Action::make('view_quotes')
                ->label('View Received Quotes')
                ->icon('heroicon-o-inbox')
                ->color('info')
                ->url(\App\Filament\Customer\Pages\ReceivedQuotes::getUrl())
                ->badge(fn ($record) => $record->responses()->count() > 0 ? $record->responses()->count() : null),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Request Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('Title'),
                        
                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                        
                        Infolists\Components\TextEntry::make('category.name')
                            ->label('Category'),
                        
                        Infolists\Components\TextEntry::make('stateLocation.name')
                            ->label('State'),
                        
                        Infolists\Components\TextEntry::make('cityLocation.name')
                            ->label('City')
                            ->placeholder('Whole state'),
                        
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'open' => 'success',
                                'closed' => 'gray',
                                'expired' => 'warning',
                                'accepted' => 'primary',
                                default => 'gray',
                            }),
                        
                        Infolists\Components\TextEntry::make('expires_at')
                            ->label('Expires On')
                            ->dateTime('M d, Y g:i A')
                            ->placeholder('No expiration'),
                        
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('M d, Y g:i A'),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Budget')
                    ->schema([
                        Infolists\Components\TextEntry::make('budget_min')
                            ->label('Minimum Budget')
                            ->money('NGN')
                            ->placeholder('Not specified'),
                        
                        Infolists\Components\TextEntry::make('budget_max')
                            ->label('Maximum Budget')
                            ->money('NGN')
                            ->placeholder('Not specified'),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->budget_min || $record->budget_max),
            ]);
    }
}