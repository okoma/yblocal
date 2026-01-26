<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $userType = 'customer') // 'customer' or 'business_owner'
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        if ($this->userType === 'business_owner') {
            return $this->businessOwnerWelcome($notifiable);
        }
        
        return $this->customerWelcome($notifiable);
    }

    protected function customerWelcome($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to YellowBooks Nigeria! 🎉')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Welcome to **YellowBooks Nigeria** - your trusted local business directory!')
            ->line('**Here\'s what you can do:**')
            ->line('🔍 **Discover** thousands of verified local businesses')
            ->line('⭐ **Review** businesses you\'ve visited')
            ->line('💾 **Save** your favorite businesses for quick access')
            ->line('📧 **Contact** businesses directly through our platform')
            ->action('Start Exploring', url('/discover'))
            ->line('**Quick Tips:**')
            ->line('• Use filters to find exactly what you need')
            ->line('• Check verified badges for trusted businesses')
            ->line('• Save businesses to build your personal directory')
            ->line('Need help? Our support team is always ready to assist.')
            ->line('Welcome aboard! 🚀');
    }

    protected function businessOwnerWelcome($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to YellowBooks Nigeria - Grow Your Business! 🚀')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Welcome to **YellowBooks Nigeria**! We\'re excited to help you grow your business.')
            ->line('**Get started in 3 easy steps:**')
            ->line('1️⃣ **Complete your business profile** - Add photos, hours, and details')
            ->line('2️⃣ **Get verified** - Build trust with a verified badge')
            ->line('3️⃣ **Engage with customers** - Respond to reviews and inquiries')
            ->action('Complete Your Profile', url('/business'))
            ->line('**Premium Benefits:**')
            ->line('✓ Featured placement in search results')
            ->line('✓ Unlimited photos and videos')
            ->line('✓ Advanced analytics and insights')
            ->line('✓ Priority support')
            ->action('Explore Premium', url('/business/subscription'))
            ->line('Have questions? Our business success team is here to help!')
            ->line('Let\'s grow together! 🌱');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'welcome',
            'user_type' => $this->userType,
            'message' => 'Welcome to YellowBooks Nigeria! 🎉',
            'url' => $this->userType === 'business_owner' ? '/business' : '/discover',
        ];
    }
}
