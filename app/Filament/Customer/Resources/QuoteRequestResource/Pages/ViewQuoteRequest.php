<?php

namespace App\Filament\Customer\Resources\QuoteRequestResource\Pages;

use App\Filament\Customer\Resources\QuoteRequestResource;
use App\Models\Notification as NotificationModel;
use App\Notifications\QuoteShortlistedNotification;
use App\Notifications\QuoteAcceptedNotification;
use App\Notifications\QuoteRejectedNotification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use App\Models\QuoteResponse;

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
                
                Infolists\Components\Section::make('All Quotes Received')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('responses')
                            ->label('')
                            ->getStateUsing(fn ($record) => $record->responses()->where('status', '!=', 'shortlisted')->get())
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
                                
                                Infolists\Components\Actions::make([
                                    Infolists\Components\Actions\Action::make('shortlist')
                                        ->label('Shortlist')
                                        ->icon('heroicon-o-star')
                                        ->color('info')
                                        ->requiresConfirmation()
                                        ->modalHeading('Shortlist Quote')
                                        ->modalDescription('Add this quote to your shortlist for comparison.')
                                        ->action(function ($record) {
                                            $record->shortlist();
                                            
                                            // Notify business
                                            try {
                                                $business = $record->business;
                                                if ($business && $business->user) {
                                                    NotificationModel::send(
                                                        userId: $business->user->id,
                                                        type: 'quote_shortlisted',
                                                        title: 'Quote Shortlisted',
                                                        message: "Your quote for '{$this->record->title}' has been shortlisted by the customer.",
                                                        actionUrl: \App\Filament\Business\Resources\QuoteResponseResource::getUrl('view', ['record' => $record->id], panel: 'business'),
                                                        extraData: [
                                                            'quote_request_id' => $this->record->id,
                                                            'quote_response_id' => $record->id,
                                                        ]
                                                    );
                                                }
                                                
                                                // Send Telegram notification to customer if enabled
                                                $customer = $this->record->user;
                                                if ($customer) {
                                                    $preferences = $customer->preferences;
                                                    if ($preferences && 
                                                        $preferences->notify_quote_updates_telegram && 
                                                        $preferences->telegram_notifications &&
                                                        $preferences->getTelegramIdentifier()) {
                                                        
                                                        try {
                                                            \Illuminate\Support\Facades\Log::info('Telegram quote update notification (pending API integration)', [
                                                                'user_id' => $customer->id,
                                                                'quote_response_id' => $record->id,
                                                                'action' => 'shortlisted',
                                                                'telegram_id' => $preferences->getTelegramIdentifier(),
                                                            ]);
                                                        } catch (\Exception $e) {
                                                            \Illuminate\Support\Facades\Log::error('Failed to send Telegram quote update notification', [
                                                                'user_id' => $customer->id,
                                                                'error' => $e->getMessage(),
                                                            ]);
                                                        }
                                                    }
                                                }
                                            } catch (\Exception $e) {
                                                Log::error('Failed to send shortlist notification', [
                                                    'quote_response_id' => $record->id,
                                                    'error' => $e->getMessage(),
                                                ]);
                                            }
                                            
                                            Notification::make()
                                                ->title('Quote shortlisted')
                                                ->success()
                                                ->send();
                                        })
                                        ->visible(fn ($record) => $record->status === 'submitted' && $this->record->status === 'open'),
                                    
                                    Infolists\Components\Actions\Action::make('accept')
                                        ->label('Accept Quote')
                                        ->icon('heroicon-o-check-circle')
                                        ->color('success')
                                        ->requiresConfirmation()
                                        ->modalHeading('Accept Quote')
                                        ->modalDescription('Are you sure you want to accept this quote? This will close the request and reject all other quotes.')
                                        ->action(function ($record) {
                                            $record->accept();
                                            
                                            // Notify business
                                            try {
                                                $business = $record->business;
                                                if ($business && $business->user) {
                                                    $business->user->notify(new QuoteAcceptedNotification($record));
                                                }
                                                
                                                $customer = $this->record->user;
                                                if ($customer) {
                                                    $preferences = $customer->preferences;
                                                    if ($preferences && 
                                                        $preferences->notify_quote_updates_telegram && 
                                                        $preferences->telegram_notifications &&
                                                        $preferences->getTelegramIdentifier()) {
                                                        
                                                        try {
                                                            \Illuminate\Support\Facades\Log::info('Telegram quote update notification (pending API integration)', [
                                                                'user_id' => $customer->id,
                                                                'quote_response_id' => $record->id,
                                                                'action' => 'accepted',
                                                                'telegram_id' => $preferences->getTelegramIdentifier(),
                                                            ]);
                                                        } catch (\Exception $e) {
                                                            \Illuminate\Support\Facades\Log::error('Failed to send Telegram quote update notification', [
                                                                'user_id' => $customer->id,
                                                                'error' => $e->getMessage(),
                                                            ]);
                                                        }
                                                    }
                                                }
                                            } catch (\Exception $e) {
                                                Log::error('Failed to send acceptance notification', [
                                                    'quote_response_id' => $record->id,
                                                    'error' => $e->getMessage(),
                                                ]);
                                            }
                                            
                                            Notification::make()
                                                ->title('Quote accepted')
                                                ->body('The quote request has been closed and other quotes have been rejected.')
                                                ->success()
                                                ->send();
                                        })
                                        ->visible(fn ($record) => in_array($record->status, ['submitted', 'shortlisted']) && $this->record->status === 'open'),
                                    
                                    Infolists\Components\Actions\Action::make('reject')
                                        ->label('Reject')
                                        ->icon('heroicon-o-x-circle')
                                        ->color('danger')
                                        ->requiresConfirmation()
                                        ->modalHeading('Reject Quote')
                                        ->modalDescription('Are you sure you want to reject this quote?')
                                        ->action(function ($record) {
                                            $record->reject();
                                            
                                            // Notify business
                                            try {
                                                $business = $record->business;
                                                if ($business && $business->user) {
                                                    $business->user->notify(new QuoteRejectedNotification($record));
                                                }
                                                
                                                $customer = $this->record->user;
                                                if ($customer) {
                                                    $preferences = $customer->preferences;
                                                    if ($preferences && 
                                                        $preferences->notify_quote_updates_telegram && 
                                                        $preferences->telegram_notifications &&
                                                        $preferences->getTelegramIdentifier()) {
                                                        
                                                        try {
                                                            \Illuminate\Support\Facades\Log::info('Telegram quote update notification (pending API integration)', [
                                                                'user_id' => $customer->id,
                                                                'quote_response_id' => $record->id,
                                                                'action' => 'rejected',
                                                                'telegram_id' => $preferences->getTelegramIdentifier(),
                                                            ]);
                                                        } catch (\Exception $e) {
                                                            \Illuminate\Support\Facades\Log::error('Failed to send Telegram quote update notification', [
                                                                'user_id' => $customer->id,
                                                                'error' => $e->getMessage(),
                                                            ]);
                                                        }
                                                    }
                                                }
                                            } catch (\Exception $e) {
                                                Log::error('Failed to send rejection notification', [
                                                    'quote_response_id' => $record->id,
                                                    'error' => $e->getMessage(),
                                                ]);
                                            }
                                            
                                            Notification::make()
                                                ->title('Quote rejected')
                                                ->success()
                                                ->send();
                                        })
                                        ->visible(fn ($record) => in_array($record->status, ['submitted', 'shortlisted']) && $this->record->status === 'open'),
                                ])
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ])
                    ->id('quotes')
                    ->visible(fn ($record) => $record->responses()->count() > 0),
            ]);
    }
    
    public function acceptQuote($quoteResponseId): void
    {
        $quoteResponse = QuoteResponse::findOrFail($quoteResponseId);
        
        if ($quoteResponse->quote_request_id !== $this->record->id) {
            Notification::make()
                ->title('Invalid quote')
                ->danger()
                ->send();
            return;
        }
        
        if ($this->record->status !== 'open') {
            Notification::make()
                ->title('Request is closed')
                ->danger()
                ->send();
            return;
        }
        
        $quoteResponse->accept();
        
        // Notify business
        try {
            $business = $quoteResponse->business;
            if ($business && $business->user) {
                $business->user->notify(new QuoteAcceptedNotification($quoteResponse));
            }
            
            $customer = $this->record->user;
            if ($customer) {
                $preferences = $customer->preferences;
                if ($preferences && 
                    $preferences->notify_quote_updates_telegram && 
                    $preferences->telegram_notifications &&
                    $preferences->getTelegramIdentifier()) {
                    
                    try {
                        \Illuminate\Support\Facades\Log::info('Telegram quote update notification (pending API integration)', [
                            'user_id' => $customer->id,
                            'quote_response_id' => $quoteResponse->id,
                            'action' => 'accepted',
                            'telegram_id' => $preferences->getTelegramIdentifier(),
                        ]);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to send Telegram quote update notification', [
                            'user_id' => $customer->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send acceptance notification', [
                'quote_response_id' => $quoteResponse->id,
                'error' => $e->getMessage(),
            ]);
        }
        
        Notification::make()
            ->title('Quote accepted')
            ->body('The quote request has been closed and other quotes have been rejected.')
            ->success()
            ->send();
    }
}
