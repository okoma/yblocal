<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InquiryResponseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Lead $lead)
    {
    }

    /**
     * Determine which channels the notification should be sent on.
     */
    public function via($notifiable): array
    {
        $channels = [];
        
        $preferences = $notifiable->preferences;
        
        // Check if user wants email notification
        if ($preferences && $preferences->notify_inquiry_response_received) {
            $channels[] = 'mail';
        }
        
        // Check if user wants in-app notification
        if ($preferences && $preferences->notify_inquiry_response_app) {
            $channels[] = 'database';
        }
        
        // Note: Telegram notifications are handled separately via custom service
        // Check is done in the notification sending logic
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $business = $this->lead->business;
        $businessUrl = $business->getUrl();
        
        return (new MailMessage)
            ->subject($business->business_name . ' responded to your inquiry')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Good news! **' . $business->business_name . '** has responded to your inquiry.')
            ->line('**Inquiry Type:** ' . $this->lead->lead_button_text)
            ->line('**Their Response:**')
            ->line('"' . $this->lead->reply_message . '"')
            ->line('**Contact Information:**')
            ->line('📞 Phone: ' . ($business->phone ?? 'Not provided'))
            ->line('📧 Email: ' . ($business->email ?? 'Not provided'))
            ->action('View Full Response', url('/customer/my-inquiries/' . $this->lead->id))
            ->line('You can reply directly to this business using the contact information above.')
            ->line('Thank you for using YellowBooks Nigeria!');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        $business = $this->lead->business;
        
        return [
            'type' => 'new_lead', // Using existing type for inquiry responses
            'lead_id' => $this->lead->id,
            'business_id' => $business->id,
            'business_name' => $business->business_name,
            'business_logo' => $business->logo,
            'inquiry_type' => $this->lead->lead_button_text,
            'reply_message' => $this->lead->reply_message,
            'url' => '/customer/my-inquiries/' . $this->lead->id,
            'message' => $business->business_name . ' responded to your ' . $this->lead->lead_button_text . ' inquiry',
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
