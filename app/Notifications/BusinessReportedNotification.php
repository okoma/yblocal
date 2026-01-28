<?php

namespace App\Notifications;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BusinessReportedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Business $business,
        public string $reportReason,
        public ?string $reporterEmail = null
    ) {
    }

    public function via($notifiable): array
    {
        $channels = ['database'];
        
        $preferences = $notifiable->preferences;
        if ($preferences && $preferences->notify_business_reported) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Business Report - ' . $this->business->business_name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your business **' . $this->business->business_name . '** has been reported by a user.')
            ->line('**Report Reason:** ' . $this->reportReason)
            ->line('**What happens next:**')
            ->line('1. Our team will review the report')
            ->line('2. We may contact you for clarification')
            ->line('3. If the report is valid, we\'ll work with you to resolve it')
            ->line('**Important:** Most reports are resolved without any action needed.')
            ->line('If you have any concerns, please contact us immediately.')
            ->action('Contact Support', url('/contact'))
            ->line('We\'re here to help: support@yellowbooks.ng');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'business_reported',
            'business_id' => $this->business->id,
            'business_name' => $this->business->business_name,
            'report_reason' => $this->reportReason,
            'url' => '/business',
            'message' => 'Your business ' . $this->business->business_name . ' has been reported',
        ];
    }
}
