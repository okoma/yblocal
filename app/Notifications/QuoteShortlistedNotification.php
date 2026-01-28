<?php

namespace App\Notifications;

use App\Models\QuoteResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteShortlistedNotification extends Notification implements ShouldQueue
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
        
        // Note: Business owners should be notified about quote status changes
        // This uses the default notification preferences for business updates
        $preferences = $notifiable->preferences;
        
        // For now, always send email for important status updates
        // You can add a specific preference later if needed
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
            ->subject('⭐ Quote Shortlisted - ' . $quoteRequest->title)
            ->greeting('Congratulations ' . $notifiable->name . '!')
            ->line('Great news! Your quote for **' . $quoteRequest->title . '** has been shortlisted by the customer.')
            ->line('**Quote Request:** ' . $quoteRequest->title)
            ->line('**Your Quote:**')
            ->line('💰 **Price:** ₦' . number_format($this->quoteResponse->price, 2))
            ->line('⏱️ **Delivery Time:** ' . $this->quoteResponse->delivery_time)
            ->line('**What this means:**')
            ->line('✅ The customer is seriously considering your quote')
            ->line('✅ You\'re one step closer to winning this project')
            ->line('✅ Stay responsive - the customer may have questions')
            ->action('View Quote Details', \App\Filament\Business\Resources\QuoteResponseResource::getUrl('view', ['record' => $this->quoteResponse->id], panel: 'business'))
            ->line('💡 **Tip:** Being shortlisted is a great sign! The customer may contact you soon.')
            ->line('Keep up the excellent work!');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        $quoteRequest = $this->quoteResponse->quoteRequest;
        $business = $this->quoteResponse->business;
        
        return [
            'type' => 'quote_shortlisted',
            'quote_request_id' => $quoteRequest->id,
            'quote_response_id' => $this->quoteResponse->id,
            'business_id' => $business->id,
            'url' => \App\Filament\Business\Resources\QuoteResponseResource::getUrl('view', ['record' => $this->quoteResponse->id], panel: 'business'),
            'message' => "Your quote for '{$quoteRequest->title}' has been shortlisted",
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
