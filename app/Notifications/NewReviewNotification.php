<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReviewNotification extends Notification implements ShouldQueue
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
        
        // Email notification
        if ($preferences && $preferences->notify_new_reviews) {
            $channels[] = 'mail';
        }
        
        // Database/In-app notification
        $channels[] = 'database';
        
        // TODO: Add Telegram channel if enabled
        // if ($preferences && $preferences->notify_new_reviews_telegram && $preferences->telegram_chat_id) {
        //     $channels[] = 'telegram';
        // }
        
        // TODO: Add WhatsApp channel if enabled and verified
        // if ($preferences && $preferences->notify_new_reviews_whatsapp && $preferences->whatsapp_verified) {
        //     $channels[] = 'whatsapp';
        // }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $business = $this->review->reviewable;
        $stars = str_repeat('⭐', $this->review->rating);
        
        return (new MailMessage)
            ->subject('⭐ New Review: ' . $this->review->rating . ' stars for ' . $business->business_name)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('You have received a new review for **' . $business->business_name . '**.')
            ->line('**Rating:** ' . $stars . ' (' . $this->review->rating . '/5)')
            ->line('**Customer:** ' . ($this->review->user->name ?? 'Anonymous'))
            ->line('**Review:**')
            ->line('"' . $this->review->comment . '"')
            ->when($this->review->is_approved, function($mail) {
                $mail->line('✅ This review has been approved and is now visible to customers.');
            }, function($mail) {
                $mail->line('⏳ This review is pending approval by our team.');
            })
            ->action('View Review & Reply', url('/business/reviews/' . $this->review->id))
            ->line('💡 **Tip:** Responding to reviews shows customers you care and can improve your rating!')
            ->line('Thank you for being part of YellowBooks Nigeria.');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        $business = $this->review->reviewable;
        
        return [
            'type' => 'new_review',
            'review_id' => $this->review->id,
            'business_id' => $business->id,
            'business_name' => $business->business_name,
            'customer_name' => $this->review->user->name ?? 'Anonymous',
            'rating' => $this->review->rating,
            'comment' => \Illuminate\Support\Str::limit($this->review->comment, 100),
            'is_approved' => $this->review->is_approved,
            'url' => '/business/reviews/' . $this->review->id,
            'message' => 'New ' . $this->review->rating . '-star review from ' . ($this->review->user->name ?? 'a customer'),
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
