<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PromotionalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public ?string $actionText = null,
        public ?string $actionUrl = null,
        public ?string $imageUrl = null
    ) {
    }

    public function via($notifiable): array
    {
        $preferences = $notifiable->preferences;
        $channels = [];
        
        if ($preferences && $preferences->notify_promotions_customer) {
            $channels[] = 'mail';
        }
        
        if ($preferences && $preferences->notify_promotions_app) {
            $channels[] = 'database';
        }
        
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->message);
        
        if ($this->actionText && $this->actionUrl) {
            $mail->action($this->actionText, $this->actionUrl);
        }
        
        $mail->line('This is a promotional message from YellowBooks Nigeria.')
            ->line('You can manage your notification preferences in your account settings.');
        
        return $mail;
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'promotional',
            'title' => $this->title,
            'message' => $this->message,
            'action_text' => $this->actionText,
            'url' => $this->actionUrl,
            'image_url' => $this->imageUrl,
        ];
    }
}
