<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Review $review)
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
        if ($preferences && $preferences->notify_review_reply_received) {
            $channels[] = 'mail';
        }
        
        // Check if user wants in-app notification
        if ($preferences && $preferences->notify_review_reply_app) {
            $channels[] = 'database';
        }
        
        // Check if user wants Telegram notification
        // TODO: Implement Telegram channel when Telegram API is integrated
        // if ($preferences && $preferences->notify_review_reply_received_telegram && $preferences->getTelegramIdentifier()) {
        //     $channels[] = 'telegram';
        // }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $business = $this->review->reviewable;
        $businessUrl = $business->getUrl();
        
        return (new MailMessage)
            ->subject($business->business_name . ' replied to your review')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Great news! **' . $business->business_name . '** has replied to your review.')
            ->line('**Your Review:**')
            ->line('"' . \Illuminate\Support\Str::limit($this->review->comment, 100) . '"')
            ->line('**Their Reply:**')
            ->line('"' . $this->review->reply . '"')
            ->action('View Full Review & Reply', url('/customer/my-reviews/' . $this->review->id))
            ->line('Thank you for sharing your experience!')
            ->line('If you have any questions, feel free to reach out.');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        $business = $this->review->reviewable;
        
        return [
            'type' => 'review_reply',
            'review_id' => $this->review->id,
            'business_id' => $business->id,
            'business_name' => $business->business_name,
            'business_logo' => $business->logo,
            'reply' => $this->review->reply,
            'url' => '/customer/my-reviews/' . $this->review->id,
            'message' => $business->business_name . ' replied to your review',
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
