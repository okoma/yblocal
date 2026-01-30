<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\BusinessClaim;

class BusinessClaimSubmitted extends Notification
{
    use Queueable;

    public function __construct(public BusinessClaim $claim)
    {
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $business = $this->claim->business;
        return (new MailMessage)
            ->subject("New claim submitted for {$business->business_name}")
            ->line('A new ownership claim has been submitted.')
            ->line('Name: ' . $this->claim->name)
            ->line('Email: ' . $this->claim->email)
            ->line('Message: ' . ($this->claim->message ?? '-'))
            ->action('View Claim', url('/admin/resources/business-claim-resources/' . $this->claim->id));
    }

    public function toArray($notifiable)
    {
        return [
            'claim_id' => $this->claim->id,
            'business_id' => $this->claim->business_id,
            'name' => $this->claim->name,
        ];
    }
}
