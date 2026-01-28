<?php

namespace App\Notifications;

use App\Models\QuoteResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewQuoteResponseNotification extends Notification implements ShouldQueue
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
        
        $preferences = $notifiable->preferences;
        
        // Email notification
        if ($preferences && $preferences->notify_quote_responses) {
            $channels[] = 'mail';
        }
        
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
            ->subject('💼 New Quote Received - ' . $quoteRequest->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Great news! **' . $business->business_name . '** has submitted a quote for your request.')
            ->line('**Quote Request:** ' . $quoteRequest->title)
            ->line('**Business:** ' . $business->business_name)
            ->line('**Quote Details:**')
            ->line('💰 **Price:** ₦' . number_format($this->quoteResponse->price, 2))
            ->line('⏱️ **Delivery Time:** ' . $this->quoteResponse->delivery_time)
            ->when($this->quoteResponse->notes, function($mail) {
                $mail->line('📝 **Notes:**')
                    ->line('"' . \Illuminate\Support\Str::limit($this->quoteResponse->notes, 200) . '"');
            })
            ->action('View Quote & Respond', \App\Filament\Customer\Resources\QuoteRequestResource::getUrl('view', ['record' => $quoteRequest->id], panel: 'customer'))
            ->line('💡 **Tip:** You can shortlist, accept, or reject quotes. Compare all quotes before making a decision!')
            ->line('Thank you for using YellowBooks Nigeria!');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        $quoteRequest = $this->quoteResponse->quoteRequest;
        $business = $this->quoteResponse->business;
        
        return [
            'type' => 'new_quote_response',
            'quote_request_id' => $quoteRequest->id,
            'quote_response_id' => $this->quoteResponse->id,
            'business_id' => $business->id,
            'business_name' => $business->business_name,
            'price' => $this->quoteResponse->price,
            'url' => \App\Filament\Customer\Resources\QuoteRequestResource::getUrl('view', ['record' => $quoteRequest->id], panel: 'customer'),
            'message' => $business->business_name . ' has submitted a quote for your request ' . $quoteRequest->title,
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
