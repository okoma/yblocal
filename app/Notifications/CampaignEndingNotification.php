<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignEndingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $campaign, // Campaign model (if you have one)
        public int $daysLeft
    ) {
    }

    public function via($notifiable): array
    {
        $preferences = $notifiable->preferences;
        $channels = ['database'];
        
        if ($preferences && $preferences->notify_campaign_updates) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $campaignName = $this->campaign->name ?? 'Your campaign';
        
        return (new MailMessage)
            ->subject('Campaign Ending Soon - ' . $campaignName)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your advertising campaign "**' . $campaignName . '**" will end in **' . $this->daysLeft . ' days**.')
            ->line('**Campaign Performance:**')
            ->line('• Impressions: ' . number_format($this->campaign->impressions ?? 0))
            ->line('• Clicks: ' . number_format($this->campaign->clicks ?? 0))
            ->line('• Engagement Rate: ' . number_format($this->campaign->engagement_rate ?? 0, 2) . '%')
            ->line('**Want to continue?**')
            ->line('Renew your campaign to maintain visibility and momentum.')
            ->action('Renew Campaign', url('/business/campaigns/' . $this->campaign->id . '/renew'))
            ->line('Need to adjust your campaign? Our team can help optimize it.')
            ->line('Contact: campaigns@yellowbooks.ng');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'campaign_ending',
            'campaign_id' => $this->campaign->id,
            'campaign_name' => $this->campaign->name ?? 'Campaign',
            'days_left' => $this->daysLeft,
            'url' => '/business/campaigns/' . $this->campaign->id,
            'message' => 'Campaign "' . ($this->campaign->name ?? 'Your campaign') . '" ends in ' . $this->daysLeft . ' days',
        ];
    }
}
