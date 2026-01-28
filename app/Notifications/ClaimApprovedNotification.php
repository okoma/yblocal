<?php

namespace App\Notifications;

use App\Models\BusinessClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClaimApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BusinessClaim $claim)
    {
    }

    public function via($notifiable): array
    {
        $channels = ['database'];
        
        $preferences = $notifiable->preferences;
        if ($preferences && $preferences->notify_claim_approved) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $business = $this->claim->business;
        
        return (new MailMessage)
            ->subject('🎉 Claim Approved - Welcome to ' . $business->business_name)
            ->greeting('Congratulations ' . $notifiable->name . '!')
            ->line('Great news! Your claim for **' . $business->business_name . '** has been approved.')
            ->line('✅ You now have full control of your business listing.')
            ->line('**What you can do now:**')
            ->line('• Update business information, hours, and photos')
            ->line('• Respond to customer reviews and inquiries')
            ->line('• View analytics and insights')
            ->line('• Upgrade to Premium for more features')
            ->action('Manage Your Business', url('/business'))
            ->line('Welcome to YellowBooks Nigeria! We\'re excited to help you grow your business.')
            ->line('Need help getting started? Check our business owner guide.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'claim_approved',
            'claim_id' => $this->claim->id,
            'business_id' => $this->claim->business_id,
            'business_name' => $this->claim->business->business_name,
            'url' => '/business',
            'message' => 'Your claim for ' . $this->claim->business->business_name . ' has been approved! 🎉',
        ];
    }
}
