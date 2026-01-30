<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\QuoteRequest;

class QuoteRequestCreated extends Notification
{
    use Queueable;

    public function __construct(public QuoteRequest $quoteRequest)
    {
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('New Quote Request Submitted')
            ->line('A customer has submitted a new quote request.')
            ->line('Title: ' . $this->quoteRequest->title)
            ->action('View Request', url('/admin/resources/quote-request-resources/' . $this->quoteRequest->id));
    }

    public function toArray($notifiable)
    {
        return [
            'quote_request_id' => $this->quoteRequest->id,
            'user_id' => $this->quoteRequest->user_id,
        ];
    }
}
