<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\BusinessReport;

class BusinessReported extends Notification
{
    use Queueable;

    public function __construct(public BusinessReport $report)
    {
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $business = $this->report->business;
        return (new MailMessage)
            ->subject("New report submitted for {$business->business_name}")
            ->line('A new report has been submitted for this business.')
            ->line('Reason: ' . $this->report->reason)
            ->line('Details: ' . ($this->report->details ?? '-'))
            ->action('View Report', url('/admin/resources/business-report-resources/' . $this->report->id));
    }

    public function toArray($notifiable)
    {
        return [
            'report_id' => $this->report->id,
            'business_id' => $this->report->business_id,
            'reason' => $this->report->reason,
        ];
    }
}
