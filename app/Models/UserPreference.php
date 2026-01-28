<?php
// ============================================
// app/Models/UserPreference.php
// User preferences model
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        // Business Owner Notification Preferences
        'email_notifications',
        'telegram_notifications',
        'telegram_username',
        'telegram_chat_id',
        'notify_new_leads_telegram',
        'notify_new_reviews_telegram',
        'notify_review_replies_telegram',
        'notify_verifications_telegram',
        'notify_premium_expiring_telegram',
        'notify_campaign_updates_telegram',
        'whatsapp_notifications',
        'whatsapp_number',
        'notify_new_leads_whatsapp',
        'notify_new_reviews_whatsapp',
        'notify_new_quote_requests_whatsapp',
        'whatsapp_verified',
        'whatsapp_verification_code',
        'whatsapp_verified_at',
        'notify_new_leads',
        'notify_new_reviews',
        'notify_review_replies',
        'notify_verifications',
        'notify_premium_expiring',
        'notify_campaign_updates',
        // Business: Claim notifications
        'notify_claim_submitted',
        'notify_claim_approved',
        'notify_claim_rejected',
        // Business: Quote notifications
        'notify_new_quote_requests',
        // Business: Business reported
        'notify_business_reported',
        // Business: Claim notifications (telegram)
        'notify_claim_submitted_telegram',
        'notify_claim_approved_telegram',
        'notify_claim_rejected_telegram',
        // Business: Quote notifications (telegram)
        'notify_new_quote_requests_telegram',
        // Business: Business reported (telegram)
        'notify_business_reported_telegram',
        // Customer Notification Preferences (when businesses interact with them)
        'notify_review_reply_received',
        'notify_inquiry_response_received',
        'notify_saved_business_updates',
        'notify_promotions_customer',
        'notify_newsletter_customer',
        'notify_review_reply_app',
        'notify_inquiry_response_app',
        'notify_saved_business_updates_app',
        'notify_promotions_app',
        // Customer: Quote notifications
        'notify_quote_responses',
        'notify_quote_updates',
        'notify_quote_responses_app',
        'notify_quote_updates_app',
        // Customer: Telegram notifications
        'notify_review_reply_received_telegram',
        'notify_inquiry_response_received_telegram',
        'notify_saved_business_updates_telegram',
        'notify_promotions_customer_telegram',
        'notify_newsletter_customer_telegram',
        'notify_quote_responses_telegram',
        'notify_quote_updates_telegram',
        // Display Preferences
        'theme',
        'language',
        'timezone',
        'date_format',
        'time_format',
        // Privacy Preferences
        'profile_visibility',
        'show_email',
        'show_phone',
    ];

    protected $casts = [
        'email_notifications' => 'boolean',
        'telegram_notifications' => 'boolean',
        'notify_new_leads_telegram' => 'boolean',
        'notify_new_reviews_telegram' => 'boolean',
        'notify_review_replies_telegram' => 'boolean',
        'notify_verifications_telegram' => 'boolean',
        'notify_premium_expiring_telegram' => 'boolean',
        'notify_campaign_updates_telegram' => 'boolean',
        'whatsapp_notifications' => 'boolean',
        'notify_new_leads_whatsapp' => 'boolean',
        'notify_new_reviews_whatsapp' => 'boolean',
        'whatsapp_verified' => 'boolean',
        'whatsapp_verified_at' => 'datetime',
        'notify_new_leads' => 'boolean',
        'notify_new_reviews' => 'boolean',
        'notify_review_replies' => 'boolean',
        'notify_verifications' => 'boolean',
        'notify_premium_expiring' => 'boolean',
        'notify_campaign_updates' => 'boolean',
        // Business: Claim notifications
        'notify_claim_submitted' => 'boolean',
        'notify_claim_approved' => 'boolean',
        'notify_claim_rejected' => 'boolean',
        'notify_new_quote_requests' => 'boolean',
        'notify_business_reported' => 'boolean',
        // Business: Claim notifications (telegram)
        'notify_claim_submitted_telegram' => 'boolean',
        'notify_claim_approved_telegram' => 'boolean',
        'notify_claim_rejected_telegram' => 'boolean',
        'notify_new_quote_requests_telegram' => 'boolean',
        'notify_business_reported_telegram' => 'boolean',
        // Customer notification casts
        'notify_review_reply_received' => 'boolean',
        'notify_inquiry_response_received' => 'boolean',
        'notify_saved_business_updates' => 'boolean',
        'notify_promotions_customer' => 'boolean',
        'notify_newsletter_customer' => 'boolean',
        'notify_review_reply_app' => 'boolean',
        'notify_inquiry_response_app' => 'boolean',
        'notify_saved_business_updates_app' => 'boolean',
        'notify_promotions_app' => 'boolean',
        // Customer: Quote notifications
        'notify_quote_responses' => 'boolean',
        'notify_quote_updates' => 'boolean',
        'notify_quote_responses_app' => 'boolean',
        'notify_quote_updates_app' => 'boolean',
        // Customer: Telegram notifications
        'notify_review_reply_received_telegram' => 'boolean',
        'notify_inquiry_response_received_telegram' => 'boolean',
        'notify_saved_business_updates_telegram' => 'boolean',
        'notify_promotions_customer_telegram' => 'boolean',
        'notify_newsletter_customer_telegram' => 'boolean',
        'notify_quote_responses_telegram' => 'boolean',
        'notify_quote_updates_telegram' => 'boolean',
        // Other
        'show_email' => 'boolean',
        'show_phone' => 'boolean',
    ];
    
    // Helper method to get Telegram identifier (prefer chat_id, fallback to username)
    public function getTelegramIdentifier(): ?string
    {
        if ($this->telegram_chat_id) {
            return $this->telegram_chat_id;
        }
        
        return $this->telegram_username;
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper method to get or create preferences
    public static function getForUser($userId)
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                // Business Owner Email Notifications
                'email_notifications' => true,
                'telegram_notifications' => true,
                'notify_new_leads_telegram' => false,
                'notify_new_reviews_telegram' => false,
                'notify_review_replies_telegram' => false,
                'notify_verifications_telegram' => false,
                'notify_premium_expiring_telegram' => false,
                'notify_campaign_updates_telegram' => false,
                'whatsapp_notifications' => true,
                'notify_new_leads_whatsapp' => false,
                'notify_new_reviews_whatsapp' => false,
                'notify_new_quote_requests_whatsapp' => false,
                'whatsapp_verified' => false,
                'notify_new_leads' => true,
                'notify_new_reviews' => true,
                'notify_review_replies' => true,
                'notify_verifications' => true,
                'notify_premium_expiring' => true,
                'notify_campaign_updates' => true,
                // Customer Notifications (defaults)
                'notify_review_reply_received' => true,
                'notify_inquiry_response_received' => true,
                'notify_saved_business_updates' => true,
                'notify_promotions_customer' => true,
                'notify_newsletter_customer' => true,
                'notify_review_reply_app' => true,
                'notify_inquiry_response_app' => true,
                'notify_saved_business_updates_app' => true,
                'notify_promotions_app' => false, // Off by default
                // Customer: Quote notifications (defaults)
                'notify_quote_responses' => true,
                'notify_quote_updates' => true,
                'notify_quote_responses_app' => true,
                'notify_quote_updates_app' => true,
                // Customer: Telegram notifications (defaults)
                'notify_review_reply_received_telegram' => false,
                'notify_inquiry_response_received_telegram' => false,
                'notify_saved_business_updates_telegram' => false,
                'notify_promotions_customer_telegram' => false,
                'notify_newsletter_customer_telegram' => false,
                'notify_quote_responses_telegram' => false,
                'notify_quote_updates_telegram' => false,
                // Display & Privacy
                'theme' => 'system',
                'language' => 'en',
                'timezone' => 'Africa/Lagos',
                'date_format' => 'M j, Y',
                'time_format' => '12h',
                'profile_visibility' => 'public',
                'show_email' => false,
                'show_phone' => false,
            ]
        );
    }
}