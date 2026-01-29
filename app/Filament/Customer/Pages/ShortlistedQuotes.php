<?php

namespace App\Filament\Customer\Pages;

use App\Models\QuoteRequest;
use App\Models\QuoteResponse;
use App\Models\Notification as NotificationModel;
use App\Notifications\QuoteAcceptedNotification;
use Filament\Pages\Page;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ShortlistedQuotes extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-star';
    
    protected static ?string $navigationLabel = 'Shortlisted';
    
    protected static ?string $navigationGroup = 'Quote';
    
    protected static ?int $navigationSort = 2;
    
    protected static string $view = 'filament.customer.pages.shortlisted-quotes';
    
    public function getTitle(): string
    {
        return 'Shortlisted Quotes';
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record(QuoteRequest::where('user_id', Auth::id())->first())
            ->schema([
                Infolists\Components\Section::make('Shortlisted Quotes')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('responses')
                            ->label('')
                            ->getStateUsing(function () {
                                return QuoteResponse::whereHas('quoteRequest', function ($query) {
                                    $query->where('user_id', Auth::id());
                                })
                                ->where('status', 'shortlisted')
                                ->with(['business', 'quoteRequest'])
                                ->get();
                            })
                            ->schema([
                                Infolists\Components\TextEntry::make('quoteRequest.title')
                                    ->label('Quote Request')
                                    ->weight('bold')
                                    ->url(fn ($record) => \App\Filament\Customer\Resources\QuoteRequestResource::getUrl('view', ['record' => $record->quoteRequest->id])),
                                
                                Infolists\Components\TextEntry::make('business.business_name')
                                    ->label('Business')
                                    ->weight('bold')
                                    ->size('lg'),
                                
                                Infolists\Components\TextEntry::make('price')
                                    ->label('Price')
                                    ->money('NGN')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('success'),
                                
                                Infolists\Components\TextEntry::make('delivery_time')
                                    ->label('Delivery Time')
                                    ->icon('heroicon-o-clock'),
                                
                                Infolists\Components\TextEntry::make('message')
                                    ->label('Message')
                                    ->columnSpanFull()
                                    ->limit(200),
                                
                                Infolists\Components\Actions::make([
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
                                                
                                                $customer = Auth::user();
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
                                            
                                            $this->dispatch('$refresh');
                                        })
                                        ->visible(fn ($record) => $record->quoteRequest->status === 'open'),
                                    
                                    Infolists\Components\Actions\Action::make('remove_from_shortlist')
                                        ->label('Remove from Shortlist')
                                        ->icon('heroicon-o-x-mark')
                                        ->color('gray')
                                        ->requiresConfirmation()
                                        ->modalHeading('Remove from Shortlist')
                                        ->modalDescription('This quote will be moved back to submitted status.')
                                        ->action(function ($record) {
                                            $record->update(['status' => 'submitted']);
                                            
                                            Notification::make()
                                                ->title('Removed from shortlist')
                                                ->success()
                                                ->send();
                                            
                                            $this->dispatch('$refresh');
                                        })
                                        ->visible(fn ($record) => $record->quoteRequest->status === 'open'),
                                ])
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ])
                    ->visible(fn () => QuoteResponse::whereHas('quoteRequest', function ($query) {
                        $query->where('user_id', Auth::id());
                    })->where('status', 'shortlisted')->count() > 0),
            ]);
    }
    
    public static function getNavigationBadge(): ?string
    {
        $count = QuoteResponse::whereHas('quoteRequest', function ($query) {
            $query->where('user_id', Auth::id());
        })
        ->where('status', 'shortlisted')
        ->count();
        
        return $count > 0 ? (string) $count : null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
}
