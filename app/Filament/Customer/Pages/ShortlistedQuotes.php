<?php

namespace App\Filament\Customer\Pages;

use App\Models\QuoteResponse;
use App\Notifications\QuoteAcceptedNotification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ShortlistedQuotes extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-star';
    
    protected static ?string $navigationLabel = 'Shortlisted';
    
    protected static ?string $navigationGroup = 'Quote';
    
    protected static ?int $navigationSort = 2;
    
    protected static string $view = 'filament.customer.pages.shortlisted-quotes';
    
    public function getTitle(): string
    {
        return 'Shortlisted Quotes';
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                QuoteResponse::query()
                    ->whereHas('quoteRequest', function ($query) {
                        $query->where('user_id', Auth::id());
                    })
                    ->where('status', 'shortlisted')
                    ->with(['business', 'quoteRequest'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('quoteRequest.title')
                    ->label('Quote Request')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn ($record) => \App\Filament\Customer\Resources\QuoteRequestResource::getUrl('view', ['record' => $record->quoteRequest->id]))
                    ->color('primary'),
                
                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('NGN')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                
                Tables\Columns\TextColumn::make('delivery_time')
                    ->label('Delivery Time')
                    ->searchable()
                    ->icon('heroicon-o-clock'),
                
                Tables\Columns\TextColumn::make('message')
                    ->label('Message')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->message),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('accept')
                    ->label('Accept Quote')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Accept Quote')
                    ->modalDescription('Are you sure you want to accept this quote? This will close the request and reject all other quotes.')
                    ->action(function (QuoteResponse $record) {
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
                    })
                    ->visible(fn (QuoteResponse $record) => $record->quoteRequest->status === 'open'),
                
                Tables\Actions\Action::make('remove_from_shortlist')
                    ->label('Remove from Shortlist')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Remove from Shortlist')
                    ->modalDescription('This quote will be moved back to submitted status.')
                    ->action(function (QuoteResponse $record) {
                        $record->update(['status' => 'submitted']);
                        
                        Notification::make()
                            ->title('Removed from shortlist')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (QuoteResponse $record) => $record->quoteRequest->status === 'open'),
                
                Tables\Actions\Action::make('view')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->url(fn (QuoteResponse $record) => \App\Filament\Customer\Resources\QuoteRequestResource::getUrl('view', ['record' => $record->quoteRequest->id]) . '#shortlisted'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No shortlisted quotes')
            ->emptyStateDescription('Shortlist quotes from your quote requests to compare them here.')
            ->emptyStateIcon('heroicon-o-star');
    
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
