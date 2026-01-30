<?php

namespace App\Services;

use App\Models\EmailSuppression;
use Illuminate\Notifications\Notification;
use App\Models\NotificationPreference;

class NotificationPreferenceService
{
    /**
     * Map notification short class names to user preference fields
     */
    protected static array $mapping = [
        'NewLeadNotification' => 'notify_new_leads',
        'NewReviewNotification' => 'notify_new_reviews',
        'ReviewReplyNotification' => 'notify_review_replies',
        'VerificationApprovedNotification' => 'notify_verifications',
        'VerificationRejectedNotification' => 'notify_verifications',
        'NewsletterNotification' => 'notify_newsletter_customer',
        'PromotionalNotification' => 'notify_promotions_customer',
        'NewQuoteRequestNotification' => 'notify_new_quote_requests',
        'NewQuoteResponseNotification' => 'notify_quote_responses',
        'InquiryResponseNotification' => 'notify_inquiry_response_received',
        // add more mappings as needed
    ];

    /**
     * Determine whether we should send notification via a specific channel
     *
     * @param mixed $notifiable
     * @param Notification $notification
     * @param string $channel
     * @return bool
     */
    public static function shouldSend($notifiable, Notification $notification, string $channel): bool
    {
        // If notifiable has no email and the channel is mail, skip
        if ($channel === 'mail' && empty($notifiable->email)) {
            return false;
        }

        // Global email suppression check
        if ($channel === 'mail' && !empty($notifiable->email)) {
            if (EmailSuppression::where('email', $notifiable->email)->exists()) {
                return false;
            }
        }

        // Database/in-app channel is always allowed (unless user explicitly disabled DB notifications via preferences)
        if ($channel === 'database' || $channel === 'broadcast') {
            return true;
        }

        // Determine topic key from notification class
        $short = (new \ReflectionClass($notification))->getShortName();
        $prefKey = static::$mapping[$short] ?? null;

        // If a per-notifiable NotificationPreference exists for this topic, prefer it
        if ($prefKey) {
            $topic = $prefKey; // reuse prefKey as topic name for NotificationPreference table
            $np = NotificationPreference::for($notifiable, $topic);
            if ($np) {
                if (!$np->enabled) {
                    return false;
                }

                if ($channel === 'mail') {
                    return (bool) ($np->channels['mail'] ?? true);
                }

                if (in_array($channel, ['telegram', 'whatsapp'])) {
                    return (bool) ($np->channels[$channel] ?? ($np->channels['push'] ?? true));
                }

                return true;
            }
        }

        // Fallback to legacy UserPreference model if available
        $preferences = $notifiable->preferences ?? null;
        if (!$preferences) {
            return true;
        }

        // If we can't map the notification, be conservative and allow send
        if (!$prefKey) {
            return true;
        }

        // For mail channel, check the corresponding preference
        if ($channel === 'mail') {
            return (bool) ($preferences->{$prefKey} ?? true);
        }

        // For other channels (e.g., telegram, whatsapp) try matching suffix
        if (in_array($channel, ['telegram', 'whatsapp'])) {
            $channelKey = $prefKey . '_' . $channel;
            return (bool) ($preferences->{$channelKey} ?? ($preferences->{$prefKey} ?? true));
        }

        return true;
    }
}
