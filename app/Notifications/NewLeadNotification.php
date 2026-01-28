<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLeadNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Lead $lead)
    {
    }

    /**
     * Determine which channels the notification should be sent on.
     */
    public function via($notifiable): array
    {
        $channels = [];
        
        $preferences = $notifiable->preferences;
        
        // Email notification
        if ($preferences && $preferences->notify_new_leads) {
            $channels[] = 'mail';
        }
        
        // Database/In-app notification
        $channels[] = 'database';
        
        // TODO: Add Telegram channel if enabled
        // if ($preferences && $preferences->notify_new_leads_telegram && $preferences->telegram_chat_id) {
        //     $channels[] = 'telegram';
        // }
        
        // TODO: Add WhatsApp channel if enabled and verified
        // if ($preferences && $preferences->notify_new_leads_whatsapp && $preferences->whatsapp_verified) {
        //     $channels[] = 'whatsapp';
        // }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $business = $this->lead->business;
        
        return (new MailMessage)
            ->subject('🎉 New Lead: ' . $this->lead->lead_button_text . ' for ' . $business->business_name)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Great news! You have received a new inquiry for **' . $business->business_name . '**.')
            ->line('**Inquiry Type:** ' . $this->lead->lead_button_text)
            ->line('**Customer Details:**')
            ->line('👤 Name: ' . $this->lead->client_name)
            ->line('📧 Email: ' . $this->lead->email)
            ->line('📞 Phone: ' . ($this->lead->phone ?? 'Not provided'))
            ->line('💬 WhatsApp: ' . ($this->lead->whatsapp ?? 'Not provided'))
            ->when($this->lead->custom_fields, function($mail) {
                $mail->line('**Additional Information:**');
                foreach ($this->lead->custom_fields as $key => $value) {
                    $mail->line('• ' . ucfirst(str_replace('_', ' ', $key)) . ': ' . $value);
                }
            })
            ->action('View & Respond to Lead', url('/business/leads/' . $this->lead->id))
            ->line('⚡ **Quick Tip:** Responding within 1 hour increases conversion by 7x!')
            ->line('Don\'t miss this opportunity to grow your business.');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        $business = $this->lead->business;
        
        return [
            'type' => 'new_lead',
            'lead_id' => $this->lead->id,
            'business_id' => $business->id,
            'business_name' => $business->business_name,
            'client_name' => $this->lead->client_name,
            'inquiry_type' => $this->lead->lead_button_text,
            'email' => $this->lead->email,
            'phone' => $this->lead->phone,
            'url' => '/business/leads/' . $this->lead->id,
            'message' => 'New ' . $this->lead->lead_button_text . ' inquiry from ' . $this->lead->client_name,
        ];
    }

    /**
     * Get the Filament database notification.
     */
    public function toDatabase($notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
