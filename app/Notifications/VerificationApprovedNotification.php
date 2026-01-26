<?php

namespace App\Notifications;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationApprovedNotification extends Notification implements ShouldQueue
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
            ->subject('🎉 Verification Approved - ' . $this->business->business_name)
            ->greeting('Congratulations ' . $notifiable->name . '!')
            ->line('**' . $this->business->business_name . '** is now a verified business!')
            ->line('✅ Your business listing now displays a verified badge.')
            ->line('**What this means for you:**')
            ->line('• Customers can trust your business is legitimate')
            ->line('• Your listing appears higher in search results')
            ->line('• You stand out from competitors')
            ->line('• Access to exclusive verified business features')
            ->action('View Your Verified Listing', $this->business->getUrl())
            ->line('Thank you for being a trusted business on YellowBooks Nigeria!')
            ->line('Share your verified status with your customers on social media.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'verification_approved',
            'business_id' => $this->business->id,
            'business_name' => $this->business->business_name,
            'url' => $this->business->getUrl(),
            'message' => $this->business->business_name . ' is now verified! 🎉',
        ];
    }
}
