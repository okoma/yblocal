<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewsletterNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $subject,
        public array $sections,  // [['title' => '', 'content' => '', 'url' => ''], ...]
        public ?string $featuredBusiness = null
    ) {
    }

    public function via($notifiable): array
    {
        $preferences = $notifiable->preferences;
        $channels = [];
        
        if ($preferences && $preferences->notify_newsletter_customer) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Here\'s what\'s new at YellowBooks Nigeria:');
        
        foreach ($this->sections as $section) {
            $mail->line('**' . $section['title'] . '**')
                ->line($section['content']);
            
            if (!empty($section['url'])) {
                $mail->action($section['action_text'] ?? 'Learn More', $section['url']);
            }
        }
        
        if ($this->featuredBusiness) {
            $mail->line('**Featured Business of the Month:**')
                ->line($this->featuredBusiness);
        }
        
        $mail->line('Thank you for being part of the YellowBooks community!')
            ->line('You can manage your newsletter preferences in your account settings.');
        // Add one-click unsubscribe link for this newsletter
        $email = base64_encode($notifiable->email ?? '');
        $unsubscribeUrl = url('/unsubscribe?e=' . $email . '&t=newsletter');
        $mail->action('Unsubscribe from this newsletter', $unsubscribeUrl);

        return $mail;
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'newsletter',
            'subject' => $this->subject,
            'sections' => $this->sections,
            'featured_business' => $this->featuredBusiness,
            'url' => '/discover',
        ];
    }
}
