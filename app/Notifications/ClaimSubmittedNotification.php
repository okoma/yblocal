<?php

namespace App\Notifications;

use App\Models\BusinessClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClaimSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BusinessClaim $claim)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Business Claim Submitted - ' . $this->claim->business->business_name)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your claim for **' . $this->claim->business->business_name . '** has been submitted successfully.')
            ->line('**Status:** Under Review')
            ->line('Our team will review your claim and supporting documents. This typically takes 2-3 business days.')
            ->line('**What happens next:**')
            ->line('1. Our team verifies your ownership documents')
            ->line('2. We may contact you for additional information')
            ->line('3. You\'ll receive a notification once approved')
            ->action('View Claim Status', url('/business/claims/' . $this->claim->id))
            ->line('Thank you for claiming your business on YellowBooks Nigeria!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'claim_submitted',
            'claim_id' => $this->claim->id,
            'business_id' => $this->claim->business_id,
            'business_name' => $this->claim->business->business_name,
            'status' => $this->claim->status,
            'url' => '/business/claims/' . $this->claim->id,
            'message' => 'Your claim for ' . $this->claim->business->business_name . ' has been submitted',
        ];
    }
}
