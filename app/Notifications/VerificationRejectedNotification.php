<?php

namespace App\Notifications;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Business $business, public ?string $reason = null)
    {
    }

    public function via($notifiable): array
    {
        $preferences = $notifiable->preferences;
        $channels = ['database'];
        
        if ($preferences && $preferences->notify_verifications) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Verification Update - ' . $this->business->business_name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We\'ve reviewed the verification request for **' . $this->business->business_name . '**.');
        
        if ($this->reason) {
            $mail->line('**Reason:**')
                ->line($this->reason);
        } else {
            $mail->line('We need more information to complete your verification.');
        }
        
        $mail->line('**Common reasons for rejection:**')
            ->line('• Documents are unclear or incomplete')
            ->line('• Business information doesn\'t match documents')
            ->line('• Missing required documentation')
            ->line('You can resubmit your verification with updated documents.')
            ->action('Resubmit Verification', url('/business'))
            ->line('Need help? Contact our verification team: verify@yellowbooks.ng');
        
        return $mail;
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'verification_rejected',
            'business_id' => $this->business->id,
            'business_name' => $this->business->business_name,
            'reason' => $this->reason,
            'url' => '/business',
            'message' => 'Verification for ' . $this->business->business_name . ' needs revision',
        ];
    }
}
