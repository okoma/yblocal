<?php

namespace App\Filament\Business\Resources\QuoteResponseResource\Pages;

use App\Filament\Business\Resources\QuoteResponseResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewQuoteResponse extends ViewRecord
{
    protected static string $resource = QuoteResponseResource::class;
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Quote Request')
                    ->schema([
                        Infolists\Components\TextEntry::make('quoteRequest.title')
                            ->label('Title')
                            ->weight('bold'),
                        
                        Infolists\Components\TextEntry::make('quoteRequest.description')
                            ->label('Description')
                            ->columnSpanFull(),
                        
                        Infolists\Components\TextEntry::make('quoteRequest.category.name')
                            ->label('Category'),
                        
                        Infolists\Components\TextEntry::make('quoteRequest.stateLocation.name')
                            ->label('State'),
                        
                        Infolists\Components\TextEntry::make('quoteRequest.cityLocation.name')
                            ->label('City')
                            ->placeholder('Whole state'),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Your Quote')
                    ->schema([
                        Infolists\Components\TextEntry::make('price')
                            ->label('Price')
                            ->money('NGN')
                            ->weight('bold')
                            ->color('success'),
                        
                        Infolists\Components\TextEntry::make('delivery_time')
                            ->label('Delivery Time'),
                        
                        Infolists\Components\TextEntry::make('message')
                            ->label('Proposal Message')
                            ->columnSpanFull(),
                        
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'submitted' => 'gray',
                                'shortlisted' => 'info',
                                'accepted' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),
            ]);
    }
}
