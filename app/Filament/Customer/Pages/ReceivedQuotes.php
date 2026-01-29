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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class ReceivedQuotes extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-inbox';

    protected static ?string $navigationLabel = 'Received Quotes';

    protected static ?string $navigationGroup = 'Quote';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.customer.pages.received-quotes';

    public function getTitle(): string
    {
        return 'Received Quotes';
    }

    // Get all responses grouped by quote request
    public function getQuoteRequests()
    {
        return \App\Models\QuoteRequest::query()
            ->where('user_id', Auth::id())
            ->whereHas('responses')
            ->with(['responses.business', 'category', 'stateLocation', 'cityLocation'])
            ->orderByDesc('created_at')
            ->get();
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

    // Actions for quotes
    public function shortlistQuote($quoteResponseId): void
    {
        $quote = QuoteResponse::findOrFail($quoteResponseId);
        
        if ($quote->quoteRequest->user_id !== Auth::id()) {
            Notification::make()->title('Unauthorized')->danger()->send();
            return;
        }

        $quote->shortlist();
        $this->sendShortlistedNotification($quote);
        Notification::make()->title('Quote shortlisted')->success()->send();
    }

    public function acceptQuote($quoteResponseId): void
    {
        $quote = QuoteResponse::findOrFail($quoteResponseId);
        
        if ($quote->quoteRequest->user_id !== Auth::id()) {
            Notification::make()->title('Unauthorized')->danger()->send();
            return;
        }

        $quote->accept();
        $this->sendAcceptedNotification($quote);
        Notification::make()->title('Quote accepted')->body('The quote request has been closed and other quotes have been rejected.')->success()->send();
    }

    public function rejectQuote($quoteResponseId): void
    {
        $quote = QuoteResponse::findOrFail($quoteResponseId);
        
        if ($quote->quoteRequest->user_id !== Auth::id()) {
            Notification::make()->title('Unauthorized')->danger()->send();
            return;
        }

        $quote->reject();
        $this->sendRejectedNotification($quote);
        Notification::make()->title('Quote rejected')->success()->send();
    }

    public function removeFromShortlist($quoteResponseId): void
    {
        $quote = QuoteResponse::findOrFail($quoteResponseId);
        
        if ($quote->quoteRequest->user_id !== Auth::id()) {
            Notification::make()->title('Unauthorized')->danger()->send();
            return;
        }

        $quote->update(['status' => 'submitted']);
        Notification::make()->title('Removed from shortlist')->success()->send();
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

    public static function getNavigationBadge(): ?string
    {
        $count = QuoteResponse::whereHas('quoteRequest', function ($query) {
            $query->where('user_id', Auth::id());
        })
        ->where('status', 'submitted')
        ->count();
        
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}