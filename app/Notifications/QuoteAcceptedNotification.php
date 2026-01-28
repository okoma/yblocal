<?php

namespace App\Notifications;

use App\Models\QuoteResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteAcceptedNotification extends Notification implements ShouldQueue
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
        
        // Always send email for accepted quotes - this is important news!
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
        $customer = $quoteRequest->user;
        
        return (new MailMessage)
            ->subject('🎉 Quote Accepted! - ' . $quoteRequest->title)
            ->greeting('Congratulations ' . $notifiable->name . '!')
            ->line('**Excellent news!** Your quote has been accepted by the customer!')
            ->line('**Quote Request:** ' . $quoteRequest->title)
            ->line('**Customer:** ' . ($customer->name ?? 'Customer'))
            ->line('**Your Accepted Quote:**')
            ->line('💰 **Price:** ₦' . number_format($this->quoteResponse->price, 2))
            ->line('⏱️ **Delivery Time:** ' . $this->quoteResponse->delivery_time)
            ->line('**Next Steps:**')
            ->line('1. Contact the customer to confirm project details')
            ->line('2. Discuss timeline and deliverables')
            ->line('3. Set up payment terms if applicable')
            ->line('4. Begin working on the project')
            ->action('View Quote Details', \App\Filament\Business\Resources\QuoteResponseResource::getUrl('view', ['record' => $this->quoteResponse->id], panel: 'business'))
            ->line('🎊 **This is a big win!** Make sure to deliver excellent service to build your reputation.')
            ->line('Thank you for being part of YellowBooks Nigeria!');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        $quoteRequest = $this->quoteResponse->quoteRequest;
        $business = $this->quoteResponse->business;
        
        return [
            'type' => 'quote_accepted',
            'quote_request_id' => $quoteRequest->id,
            'quote_response_id' => $this->quoteResponse->id,
            'business_id' => $business->id,
            'url' => \App\Filament\Business\Resources\QuoteResponseResource::getUrl('view', ['record' => $this->quoteResponse->id], panel: 'business'),
            'message' => "Congratulations! Your quote for '{$quoteRequest->title}' has been accepted! 🎉",
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
