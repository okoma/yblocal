<?php

namespace App\Notifications;

use App\Models\QuoteResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public QuoteResponse $quoteResponse)
    {
    }

    /**
     * Determine which channels the notification should be sent on.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];
        
        // Send email for rejected quotes so businesses know the status
        $channels[] = 'mail';
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $quoteRequest = $this->quoteResponse->quoteRequest;
        $business = $this->quoteResponse->business;
        
        return (new MailMessage)
            ->subject('Quote Update - ' . $quoteRequest->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We wanted to let you know that your quote for **' . $quoteRequest->title . '** was not selected by the customer.')
            ->line('**Quote Request:** ' . $quoteRequest->title)
            ->line('**Your Quote:**')
            ->line('💰 **Price:** ₦' . number_format($this->quoteResponse->price, 2))
            ->line('⏱️ **Delivery Time:** ' . $this->quoteResponse->delivery_time)
            ->line('**Don\'t be discouraged!**')
            ->line('• This is part of the competitive process')
            ->line('• Keep submitting quotes - persistence pays off')
            ->line('• Consider reviewing your pricing or approach for future quotes')
            ->line('• Many successful businesses win quotes after several attempts')
            ->action('View Other Opportunities', \App\Filament\Business\Pages\AvailableQuoteRequests::getUrl())
            ->line('💪 **Keep going!** There are always new opportunities on YellowBooks Nigeria.')
            ->line('Thank you for your continued participation.');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        $quoteRequest = $this->quoteResponse->quoteRequest;
        $business = $this->quoteResponse->business;
        
        return [
            'type' => 'quote_rejected',
            'quote_request_id' => $quoteRequest->id,
            'quote_response_id' => $this->quoteResponse->id,
            'business_id' => $business->id,
            'url' => \App\Filament\Business\Resources\QuoteResponseResource::getUrl('view', ['record' => $this->quoteResponse->id], panel: 'business'),
            'message' => "Your quote for '{$quoteRequest->title}' was not selected",
        ];
    }

    /**
     * Get the Filament database notification.
     */
    public function toDatabase($notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
