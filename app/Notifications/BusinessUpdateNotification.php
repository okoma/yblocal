<?php

namespace App\Notifications;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BusinessUpdateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Business $business,
        public string $updateType,  // 'new_product', 'special_offer', 'hours_change', etc.
        public string $updateMessage
    ) {
    }

    public function via($notifiable): array
    {
        $preferences = $notifiable->preferences;
        $channels = [];
        
        if ($preferences && $preferences->notify_saved_business_updates) {
            $channels[] = 'mail';
        }
        
        if ($preferences && $preferences->notify_saved_business_updates_app) {
            $channels[] = 'database';
        }
        
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $businessUrl = $this->business->getUrl();
        
        return (new MailMessage)
            ->subject('Update from ' . $this->business->business_name)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('**' . $this->business->business_name . '**, a business you saved, has an update for you:')
            ->line($this->updateMessage)
            ->action('View Details', $businessUrl)
            ->line('You\'re receiving this because you saved this business.')
            ->line('Not interested? You can unsave businesses anytime from your dashboard.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'business_update',
            'business_id' => $this->business->id,
            'business_name' => $this->business->business_name,
            'business_logo' => $this->business->logo,
            'update_type' => $this->updateType,
            'message' => $this->updateMessage,
            'url' => $this->business->getUrl(),
        ];
    }
}
