<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PremiumExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Subscription $subscription, public int $daysLeft)
    {
    }

    public function via($notifiable): array
    {
        $preferences = $notifiable->preferences;
        $channels = ['database'];
        
        if ($preferences && $preferences->notify_premium_expiring) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $urgency = $this->daysLeft <= 3 ? '⚠️ ' : '';
        $business = $this->subscription->business;
        
        return (new MailMessage)
            ->subject($urgency . 'Premium Expiring in ' . $this->daysLeft . ' days - ' . $business->business_name)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your Premium subscription for **' . $business->business_name . '** will expire in **' . $this->daysLeft . ' days**.')
            ->line('**Expires on:** ' . $this->subscription->ends_at->format('F j, Y'))
            ->line('**Don\'t lose these benefits:**')
            ->line('✓ Featured listing placement')
            ->line('✓ Unlimited photos and videos')
            ->line('✓ Priority customer support')
            ->line('✓ Advanced analytics')
            ->line('✓ Social media integration')
            ->line('**Renew now** to maintain your premium status and visibility!')
            ->action('Renew Premium', url('/business/subscription/renew'))
            ->line('Questions about renewal? Contact our support team.')
            ->line('💡 **Early renewal bonus:** Renew now and get 10% off!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'premium_expiring',
            'subscription_id' => $this->subscription->id,
            'business_id' => $this->subscription->business_id,
            'business_name' => $this->subscription->business->business_name,
            'days_left' => $this->daysLeft,
            'expires_at' => $this->subscription->ends_at->toDateString(),
            'url' => '/business/subscription/renew',
            'message' => 'Premium expires in ' . $this->daysLeft . ' days for ' . $this->subscription->business->business_name,
        ];
    }
}
