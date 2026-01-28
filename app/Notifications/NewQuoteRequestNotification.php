<?php

namespace App\Notifications;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewQuoteRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public QuoteRequest $quoteRequest)
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
        if ($preferences && $preferences->notify_new_quote_requests) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $location = $this->quoteRequest->cityLocation?->name ?? $this->quoteRequest->stateLocation?->name ?? 'N/A';
        
        return (new MailMessage)
            ->subject('🎯 New Quote Request Available - ' . $this->quoteRequest->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A new quote request matches your business category and location.')
            ->line('**Quote Request Details:**')
            ->line('📋 **Title:** ' . $this->quoteRequest->title)
            ->line('📝 **Description:** ' . \Illuminate\Support\Str::limit($this->quoteRequest->description, 150))
            ->line('🏷️ **Category:** ' . $this->quoteRequest->category->name)
            ->line('📍 **Location:** ' . $location)
            ->line('💰 **Budget:** ' . ($this->quoteRequest->budget ? '₦' . number_format($this->quoteRequest->budget, 2) : 'Not specified'))
            ->line('📅 **Expires:** ' . $this->quoteRequest->expires_at->format('F j, Y'))
            ->action('View & Submit Quote', \App\Filament\Business\Pages\AvailableQuoteRequests::getUrl())
            ->line('⚡ **Quick Tip:** Submit your quote early to increase your chances of being selected!')
            ->line('This is a great opportunity to grow your business.');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'new_quote_request',
            'quote_request_id' => $this->quoteRequest->id,
            'title' => $this->quoteRequest->title,
            'category' => $this->quoteRequest->category->name,
            'location' => $this->quoteRequest->cityLocation?->name ?? $this->quoteRequest->stateLocation?->name,
            'url' => \App\Filament\Business\Pages\AvailableQuoteRequests::getUrl(),
            'message' => "New quote request '{$this->quoteRequest->title}' matches your business",
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
