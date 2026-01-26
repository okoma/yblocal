<?php

namespace App\Notifications;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Business $business)
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
        return (new MailMessage)
            ->subject('Verification Submitted - ' . $this->business->business_name)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your verification request for **' . $this->business->business_name . '** has been submitted.')
            ->line('**Status:** Under Review')
            ->line('Our verification team will review your documents within 2-3 business days.')
            ->line('**Benefits of verification:**')
            ->line('✓ Verified badge on your listing')
            ->line('✓ Increased customer trust')
            ->line('✓ Higher search ranking')
            ->line('✓ Access to premium features')
            ->action('View Verification Status', url('/business'))
            ->line('We\'ll notify you once the review is complete.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'verification_submitted',
            'business_id' => $this->business->id,
            'business_name' => $this->business->business_name,
            'status' => $this->business->verification_status,
            'url' => '/business',
            'message' => 'Verification request for ' . $this->business->business_name . ' submitted',
        ];
    }
}
