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
        'whatsapp_verified',
        'whatsapp_verification_code',
        'whatsapp_verified_at',
        'notify_new_leads',
        'notify_new_reviews',
        'notify_review_replies',
        'notify_verifications',
        'notify_premium_expiring',
        'notify_campaign_updates',
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