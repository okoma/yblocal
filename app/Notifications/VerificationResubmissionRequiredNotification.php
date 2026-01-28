<?php

namespace App\Notifications;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationResubmissionRequiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Business $business,
        public string $reason,
        public array $requiredDocuments = []
    ) {
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
            ->subject('Action Required: Verification Documents - ' . $this->business->business_name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your verification for **' . $this->business->business_name . '** requires additional information.')
            ->line('**What we need:**')
            ->line($this->reason);
        
        if (!empty($this->requiredDocuments)) {
            $mail->line('**Required documents:**');
            foreach ($this->requiredDocuments as $doc) {
                $mail->line('• ' . $doc);
            }
        }
        
        $mail->line('**Please resubmit within 7 days** to avoid verification cancellation.')
            ->action('Upload Documents', url('/business'))
            ->line('Questions? Our team is here to help: verify@yellowbooks.ng');
        
        return $mail;
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'verification_resubmission_required',
            'business_id' => $this->business->id,
            'business_name' => $this->business->business_name,
            'reason' => $this->reason,
            'required_documents' => $this->requiredDocuments,
            'url' => '/business',
            'message' => 'Additional documents required for ' . $this->business->business_name,
        ];
    }
}
