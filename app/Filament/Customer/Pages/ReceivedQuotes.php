<?php

namespace App\Filament\Customer\Pages;

use App\Models\Notification as NotificationModel;
use App\Models\QuoteResponse;
use App\Notifications\QuoteAcceptedNotification;
use App\Notifications\QuoteRejectedNotification;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;  // ✅ ADD THIS LINE
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class ReceivedQuotes extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-inbox';

    protected static ?string $navigationLabel = 'Received Quotes';

    protected static ?string $navigationGroup = 'Quote';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.customer.pages.received-quotes';

    public function getTitle(): string
    {
        return 'Received Quotes';
    }

    public function getShortlistedCount(): int
    {
        return QuoteResponse::query()
            ->whereHas('quoteRequest', fn ($q) => $q->where('user_id', Auth::id()))
            ->where('status', 'shortlisted')
            ->count();
    }

    public function getAcceptedCount(): int
    {
        return QuoteResponse::query()
            ->whereHas('quoteRequest', fn ($q) => $q->where('user_id', Auth::id()))
            ->where('status', 'accepted')
            ->count();
    }

    public function getRejectedCount(): int
    {
        return QuoteResponse::query()
            ->whereHas('quoteRequest', fn ($q) => $q->where('user_id', Auth::id()))
            ->where('status', 'rejected')
            ->count();
    }

    public function getShortlistedResponses()
    {
        return QuoteResponse::query()
            ->whereHas('quoteRequest', fn ($q) => $q->where('user_id', Auth::id()))
            ->where('status', 'shortlisted')
            ->with(['business', 'quoteRequest'])
            ->orderBy('price')
            ->get();
    }

    public function getAcceptedResponses()
    {
        return QuoteResponse::query()
            ->whereHas('quoteRequest', fn ($q) => $q->where('user_id', Auth::id()))
            ->where('status', 'accepted')
            ->with(['business', 'quoteRequest'])
            ->orderByDesc('updated_at')
            ->get();
    }

    public function getRejectedResponses()
    {
        return QuoteResponse::query()
            ->whereHas('quoteRequest', fn ($q) => $q->where('user_id', Auth::id()))
            ->where('status', 'rejected')
            ->with(['business', 'quoteRequest'])
            ->orderByDesc('updated_at')
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('shortlisted')
                ->label(fn () => (string) $this->getShortlistedCount() . ' Shortlisted')
                ->icon('heroicon-o-star')
                ->color('info')
                ->badge()
                ->slideOver()
                ->modalHeading('Shortlisted')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->form([
                    Forms\Components\Placeholder::make('shortlisted_content')
                        ->label('')
                        ->content(fn () => new HtmlString(
                            view('filament.customer.received-quotes.shortlisted-offcanvas', [
                                'items' => $this->getShortlistedResponses(),
                                'showCompare' => $this->getShortlistedCount() >= 2,
                            ])->render()
                        )),
                ]),

            Actions\Action::make('accepted')
                ->label(fn () => (string) $this->getAcceptedCount() . ' Accepted')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->badge()
                ->slideOver()
                ->modalHeading('Accepted')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->form([
                    Forms\Components\Placeholder::make('accepted_content')
                        ->label('')
                        ->content(fn () => new HtmlString(
                            view('filament.customer.received-quotes.list-offcanvas', [
                                'items' => $this->getAcceptedResponses(),
                                'type' => 'accepted',
                            ])->render()
                        )),
                ]),

            Actions\Action::make('rejected')
                ->label(fn () => (string) $this->getRejectedCount() . ' Rejected')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->badge()
                ->slideOver()
                ->modalHeading('Rejected')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->form([
                    Forms\Components\Placeholder::make('rejected_content')
                        ->label('')
                        ->content(fn () => new HtmlString(
                            view('filament.customer.received-quotes.list-offcanvas', [
                                'items' => $this->getRejectedResponses(),
                                'type' => 'rejected',
                            ])->render()
                        )),
                ]),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                QuoteResponse::query()
                    ->whereHas('quoteRequest', fn ($q) => $q->where('user_id', Auth::id()))
                    ->with(['business', 'quoteRequest'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('quoteRequest.title')
                    ->label('Quote Request')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn (QuoteResponse $record) => \App\Filament\Customer\Resources\QuoteRequestResource::getUrl('view', ['record' => $record->quote_request_id])),
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
                    ->icon('heroicon-o-clock'),
                Tables\Columns\TextColumn::make('message')
                    ->label('Message')
                    ->limit(40)
                    ->tooltip(fn (QuoteResponse $record) => $record->message),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'submitted' => 'gray',
                        'shortlisted' => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('shortlist')
                    ->label('Shortlist')
                    ->icon('heroicon-o-star')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Shortlist Quote')
                    ->modalDescription('Add this quote to your shortlist for comparison.')
                    ->action(function (QuoteResponse $record) {
                        $record->shortlist();
                        $this->sendShortlistedNotification($record);
                        Notification::make()->title('Quote shortlisted')->success()->send();
                    })
                    ->visible(fn (QuoteResponse $record) => $record->status === 'submitted' && $record->quoteRequest->status === 'open'),

                Tables\Actions\Action::make('accept')
                    ->label('Accept Quote')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Accept Quote')
                    ->modalDescription('Are you sure? This will close the request and reject all other quotes.')
                    ->action(function (QuoteResponse $record) {
                        $record->accept();
                        $this->sendAcceptedNotification($record);
                        Notification::make()->title('Quote accepted')->body('The quote request has been closed and other quotes have been rejected.')->success()->send();
                    })
                    ->visible(fn (QuoteResponse $record) => in_array($record->status, ['submitted', 'shortlisted']) && $record->quoteRequest->status === 'open'),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Quote')
                    ->modalDescription('Are you sure you want to reject this quote?')
                    ->action(function (QuoteResponse $record) {
                        $record->reject();
                        $this->sendRejectedNotification($record);
                        Notification::make()->title('Quote rejected')->success()->send();
                    })
                    ->visible(fn (QuoteResponse $record) => in_array($record->status, ['submitted', 'shortlisted']) && $record->quoteRequest->status === 'open'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No quotes received yet')
            ->emptyStateDescription('When businesses respond to your quote requests, they will appear here.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    protected function sendShortlistedNotification(QuoteResponse $record): void
    {
        try {
            $business = $record->business;
            if ($business && $business->user) {
                NotificationModel::send(
                    userId: $business->user->id,
                    type: 'quote_shortlisted',
                    title: 'Quote Shortlisted',
                    message: "Your quote for '{$record->quoteRequest->title}' has been shortlisted by the customer.",
                    actionUrl: \App\Filament\Business\Resources\QuoteResponseResource::getUrl('view', ['record' => $record->id], panel: 'business'),
                    extraData: ['quote_request_id' => $record->quote_request_id, 'quote_response_id' => $record->id]
                );
            }
        } catch (\Throwable $e) {
            Log::error('Shortlist notification failed', ['quote_response_id' => $record->id, 'error' => $e->getMessage()]);
        }
    }

    protected function sendAcceptedNotification(QuoteResponse $record): void
    {
        try {
            $business = $record->business;
            if ($business && $business->user) {
                $business->user->notify(new QuoteAcceptedNotification($record));
            }
        } catch (\Throwable $e) {
            Log::error('Accept notification failed', ['quote_response_id' => $record->id, 'error' => $e->getMessage()]);
        }
    }

    protected function sendRejectedNotification(QuoteResponse $record): void
    {
        try {
            $business = $record->business;
            if ($business && $business->user) {
                $business->user->notify(new QuoteRejectedNotification($record));
            }
        } catch (\Throwable $e) {
            Log::error('Reject notification failed', ['quote_response_id' => $record->id, 'error' => $e->getMessage()]);
        }
    }
}