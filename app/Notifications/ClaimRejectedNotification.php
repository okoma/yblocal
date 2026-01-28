<?php

namespace App\Notifications;

use App\Models\BusinessClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClaimRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BusinessClaim $claim, public ?string $reason = null)
    {
    }

    public function via($notifiable): array
    {
        $channels = ['database'];
        
        $preferences = $notifiable->preferences;
        if ($preferences && $preferences->notify_claim_rejected) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Claim Update - ' . $this->claim->business->business_name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We\'ve reviewed your claim for **' . $this->claim->business->business_name . '**.');
        
        if ($this->reason) {
            $mail->line('**Reason for rejection:**')
                ->line($this->reason);
        } else {
            $mail->line('Unfortunately, we were unable to verify your ownership at this time.');
        }
        
        $mail->line('**You can resubmit your claim with:**')
            ->line('• Additional proof of ownership documents')
            ->line('• Business registration certificates')
            ->line('• Utility bills or lease agreements')
            ->line('• Government-issued ID matching business registration')
            ->action('Resubmit Claim', url('/claims/create?business=' . $this->claim->business_id))
            ->line('If you believe this is an error, please contact our support team.')
            ->line('We\'re here to help: support@yellowbooks.ng');
        
        return $mail;
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'claim_rejected',
            'claim_id' => $this->claim->id,
            'business_id' => $this->claim->business_id,
            'business_name' => $this->claim->business->business_name,
            'reason' => $this->reason,
            'url' => '/claims/create?business=' . $this->claim->business_id,
            'message' => 'Your claim for ' . $this->claim->business->business_name . ' needs more information',
        ];
    }
}
