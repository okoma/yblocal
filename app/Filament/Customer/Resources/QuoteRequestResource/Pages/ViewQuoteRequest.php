<?php

namespace App\Filament\Customer\Resources\QuoteRequestResource\Pages;

use App\Filament\Customer\Resources\QuoteRequestResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Actions;
use Filament\Notifications\Notification;

class ViewQuoteRequest extends ViewRecord
{
    protected static string $resource = QuoteRequestResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_quotes')
                ->label('View All Quotes')
                ->icon('heroicon-o-document-text')
                ->url(fn () => QuoteRequestResource::getUrl('view', ['record' => $this->record]) . '#quotes'),
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
                
                Infolists\Components\Section::make('Quotes Received')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('responses')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('business.business_name')
                                    ->label('Business')
                                    ->weight('bold'),
                                
                                Infolists\Components\TextEntry::make('price')
                                    ->label('Price')
                                    ->money('NGN')
                                    ->weight('bold')
                                    ->color('success'),
                                
                                Infolists\Components\TextEntry::make('delivery_time')
                                    ->label('Delivery Time'),
                                
                                Infolists\Components\TextEntry::make('message')
                                    ->label('Message')
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
                            ->columns(3),
                    ])
                    ->id('quotes')
                    ->visible(fn ($record) => $record->responses()->count() > 0),
            ]);
    }
}
