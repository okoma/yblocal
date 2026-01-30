<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Customer Referral Commission (Customer → Business)
    |--------------------------------------------------------------------------
    |
    | When a referred business makes a payment (subscription, ad credits, 
    | quote credits, wallet funding), the referring customer earns commission
    | as real cash in their customer_referral_wallet.
    |
    */
    'customer_commission_rate' => (float) env('REFERRAL_CUSTOMER_COMMISSION_RATE', 0.10), // 10%
    
    'min_commission_amount' => (float) env('REFERRAL_MIN_COMMISSION_AMOUNT', 100), // ₦100 minimum
    'max_commission_per_transaction' => (float) env('REFERRAL_MAX_COMMISSION_PER_TRANSACTION', 50000), // ₦50,000 max
    
    // Commission calculation method
    'commission_calculation_method' => env('REFERRAL_COMMISSION_METHOD', 'gross'), // 'gross' or 'net' (after platform fees)
    
    // Which transaction types earn commission
    'eligible_transaction_types' => [
        'subscription',
        'ad_credits',
        'quote_credits',
        // 'wallet_funding', // Enable if you want commission on wallet top-ups
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Referral Credits (Business → Business)
    |--------------------------------------------------------------------------
    |
    | Credits awarded to the referring business when a referred business
    | signs up. Credits can be converted to ad credits, quote credits,
    | or 1-month subscription.
    |
    */
    'business_credits_per_signup' => (int) env('REFERRAL_BUSINESS_CREDITS_PER_SIGNUP', 100),
    
    /*
    |--------------------------------------------------------------------------
    | Business Referral Qualification Criteria
    |--------------------------------------------------------------------------
    |
    | To prevent abuse, referred businesses must meet certain criteria
    | before referring business receives credits.
    |
    */
    'business_qualification' => [
        'enabled' => (bool) env('REFERRAL_REQUIRE_QUALIFICATION', true),
        
        // Profile completion requirements
        'require_profile_completion' => true, // Must complete business profile
        'profile_completion_percentage' => 80, // At least 80% complete
        
        // Payment requirements (choose one or both)
        'require_first_payment' => true, // Must make at least one payment
        'minimum_payment_amount' => 5000, // ₦5,000 minimum payment
        
        // Time-based requirements
        'days_active_required' => 30, // Must be active for 30 days (0 = disabled)
        
        // Verification requirements
        'require_verification' => false, // Must be verified business (optional)
        'require_email_verification' => true, // Must verify email
        
        // Usage requirements
        'require_minimum_products' => 3, // Must add at least 3 products/services
        'require_minimum_photos' => 5, // Must upload at least 5 photos
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Business Referral Credit Conversion Rules
    |--------------------------------------------------------------------------
    |
    | Rules for converting referral credits to platform features.
    |
    */
    'conversion_limits' => [
        'min_credits_for_conversion' => 50, // Minimum 50 credits to convert
        'max_conversions_per_month' => 10, // Limit conversions per month
        'conversion_fee_percentage' => 0, // Optional: 5% fee on conversions
        
        // Ad Credits Conversion
        'ad_credits' => [
            'enabled' => true,
            'conversion_ratio' => '1:1', // 1 referral credit = 1 ad credit
            'min_conversion' => 50, // Minimum 50 credits
            'max_conversion_per_transaction' => 5000, // Maximum 5,000 per conversion
            'max_conversion_per_month' => 10000, // Monthly limit
        ],
        
        // Quote Credits Conversion
        'quote_credits' => [
            'enabled' => true,
            'conversion_ratio' => '1:1', // 1 referral credit = 1 quote credit
            'min_conversion' => 50, // Minimum 50 credits
            'max_conversion_per_transaction' => 500, // Maximum 500 per conversion
            'max_conversion_per_month' => 1000, // Monthly limit
        ],
        
        // Subscription Conversion
        'subscription' => [
            'enabled' => true,
            'credits_required' => (int) env('REFERRAL_CONVERSION_TO_SUBSCRIPTION_CREDITS', 500), // 500 credits = 1 month
            'validity_days' => 30, // 1 month subscription
            'max_redemptions_per_year' => 12, // Max 1 year free subscription
            'can_stack' => true, // Can redeem multiple months at once
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Customer Referral Withdrawal Settings
    |--------------------------------------------------------------------------
    |
    | Rules for withdrawing commission earnings.
    |
    */
    'withdrawal' => [
        // Amount limits
        'min_amount' => (float) env('REFERRAL_WITHDRAWAL_MIN', 5000), // ₦5,000 minimum
        'max_amount_per_request' => (float) env('REFERRAL_WITHDRAWAL_MAX_PER_REQUEST', 500000), // ₦500,000 per request
        
        // Daily and monthly limits
        'daily_limit' => (float) env('REFERRAL_WITHDRAWAL_DAILY_LIMIT', 1000000), // ₦1M per day
        'monthly_limit' => (float) env('REFERRAL_WITHDRAWAL_MONTHLY_LIMIT', 5000000), // ₦5M per month
        'max_pending_requests' => 3, // Maximum 3 pending requests at once
        
        // Processing fees
        'processing_fee_percentage' => (float) env('REFERRAL_WITHDRAWAL_FEE_PERCENTAGE', 1.5), // 1.5%
        'min_processing_fee' => (float) env('REFERRAL_WITHDRAWAL_MIN_FEE', 50), // Minimum ₦50
        'max_processing_fee' => (float) env('REFERRAL_WITHDRAWAL_MAX_FEE', 2000), // Maximum ₦2,000
        
        // Processing time
        'processing_days' => 5, // Business days to process
        'auto_approve_threshold' => 10000, // Auto-approve withdrawals under ₦10,000
        
        // Supported withdrawal methods
        'methods' => [
            'bank_transfer' => [
                'enabled' => true,
                'name' => 'Bank Transfer',
                'description' => 'Direct bank transfer to your account',
                'processing_time' => '3-5 business days',
            ],
            'mobile_money' => [
                'enabled' => false, // Enable when ready
                'name' => 'Mobile Money',
                'description' => 'Transfer to mobile money account',
                'processing_time' => '1-2 business days',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fraud Detection & Prevention
    |--------------------------------------------------------------------------
    |
    | Rules to prevent referral system abuse.
    |
    */
    'fraud_detection' => [
        'enabled' => (bool) env('REFERRAL_FRAUD_DETECTION_ENABLED', true),
        
        // IP-based limits
        'max_referrals_per_ip' => 3, // Same IP can't create more than 3 referrals
        'max_referrals_per_ip_per_day' => 1, // 1 referral per IP per day
        
        // Device-based limits
        'max_referrals_per_device' => 5, // Same device fingerprint limit
        
        // Time-based limits
        'min_time_between_referrals' => 3600, // 1 hour (in seconds)
        'max_referrals_per_user_per_day' => 5, // 5 referrals per day
        'max_referrals_per_user_per_month' => 50, // 50 referrals per month
        
        // Pattern detection
        'block_same_email_domain' => true, // Can't repeatedly refer same email domain
        'max_same_domain_referrals' => 2, // Max 2 from same domain
        
        // Verification requirements
        'require_email_verification' => true, // Referrer must verify email
        'require_phone_verification' => false, // Referrer must verify phone
        'require_referee_email_verification' => true, // Referred user must verify email
        
        // Automatic actions
        'auto_block_threshold' => 10, // Auto-block after 10 suspicious activities
        'auto_flag_threshold' => 5, // Auto-flag after 5 suspicious activities
        
        // Suspicious patterns to detect
        'detect_patterns' => [
            'rapid_signups' => true, // Multiple signups in short time
            'same_user_data' => true, // Similar names, addresses, etc.
            'vpn_usage' => false, // VPN/proxy detection (requires service)
            'disposable_emails' => true, // Temporary email services
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Referral Expiration Rules
    |--------------------------------------------------------------------------
    |
    | When referrals and credits expire.
    |
    */
    'expiration' => [
        // Customer referrals
        'customer_referral_expires' => false, // Customer referrals don't expire
        'customer_referral_days' => null, // Days until expiration (null = never)
        
        // Business referrals
        'business_referral_expires' => false, // Business referrals don't expire
        'pending_referral_days' => 90, // Pending (not qualified) referrals expire after 90 days
        
        // Referral credits
        'credits_expire' => true, // Business referral credits expire
        'credits_expiration_days' => 365, // Credits expire after 1 year
        'credits_expiration_warning_days' => 30, // Notify 30 days before expiry
        
        // Commission
        'commission_expires' => false, // Customer commission doesn't expire
        'commission_expiration_days' => null, // Days until commission expires
    ],

    /*
    |--------------------------------------------------------------------------
    | Referral Tracking & Analytics
    |--------------------------------------------------------------------------
    |
    | Settings for tracking referral performance.
    |
    */
    'tracking' => [
        'enabled' => true,
        
        // Track these events
        'track_events' => [
            'link_clicked', // When referral link is clicked
            'signup_started', // When signup form is accessed
            'signup_completed', // When account is created
            'profile_completed', // When profile is filled
            'first_login', // When user logs in first time
            'first_payment', // When first payment is made
            'qualified', // When referral becomes qualified
        ],
        
        // UTM parameters to track
        'utm_parameters' => [
            'source', // utm_source
            'medium', // utm_medium
            'campaign', // utm_campaign
            'term', // utm_term
            'content', // utm_content
        ],
        
        // Cookie settings
        'cookie_name' => 'ybn_referral',
        'cookie_duration_days' => 30, // 30 days attribution window
    ],

    /*
    |--------------------------------------------------------------------------
    | Referral Link URLs
    |--------------------------------------------------------------------------
    |
    | Base URLs for generating referral links.
    |
    */
    'urls' => [
        // Business registration URL
        'business_register' => rtrim(env('REFERRAL_BUSINESS_REGISTER_URL', 'https://biz.yellowbooks.ng/register'), '/'),
        
        // Landing pages (optional)
        'customer_landing_page' => null, // Custom landing for customer referrals
        'business_landing_page' => null, // Custom landing for business referrals
        
        // Deep links (for mobile app)
        'deep_link_scheme' => 'yellowbooks://', // yellowbooks://register?ref=CODE
    ],

    /*
    |--------------------------------------------------------------------------
    | Referral Share Templates
    |--------------------------------------------------------------------------
    |
    | Pre-defined message templates for sharing referral links.
    | Available variables: {CODE}, {LINK}, {NAME}, {BUSINESS_NAME}
    |
    */
    'share_templates' => [
        // Customer referral templates
        'customer' => [
            'sms' => 'Join YellowBooks Nigeria and grow your business! Use my referral code: {CODE} - {LINK}',
            
            'whatsapp' => '🎉 *Grow Your Business with YellowBooks Nigeria!*

I\'ve been using YellowBooks to reach more customers, and I think you\'ll love it too!

✅ Free business listing
✅ Get customer reviews
✅ Reach thousands of potential customers

*Use my referral code:* {CODE}
*Sign up here:* {LINK}',
            
            'email_subject' => 'Invitation to join YellowBooks Nigeria',
            
            'email_body' => 'Hi there!

I\'ve been using YellowBooks Nigeria to grow my business, and I think it could help your business too.

YellowBooks helps businesses like yours reach more customers through:
- Free business listings
- Customer reviews
- Lead generation
- Analytics

Use my referral code {CODE} to get started: {LINK}

Let me know if you have any questions!

Best regards,
{NAME}',
        ],
        
        // Business referral templates
        'business' => [
            'sms' => 'Grow your business with YellowBooks Nigeria! Use my code: {CODE} - {LINK}',
            
            'whatsapp' => '📈 *Join YellowBooks Nigeria Business Network*

Hi! I wanted to share YellowBooks with you - it\'s helped {BUSINESS_NAME} reach more customers.

*Benefits:*
✨ Free business listing
📊 Analytics & insights
⭐ Customer reviews
🎯 Lead generation

*Use my referral code:* {CODE}
*Get started:* {LINK}

Plus, we both get bonus credits! 🎁',
            
            'email_subject' => 'Invitation to YellowBooks Nigeria Business Network',
            
            'email_body' => 'Hello!

I wanted to invite you to join YellowBooks Nigeria, where I\'ve listed {BUSINESS_NAME}.

YellowBooks has helped us:
- Reach thousands of potential customers
- Get verified reviews
- Generate quality leads
- Track our performance

Use my referral code {CODE} to sign up: {LINK}

As a bonus, we both get referral credits when you join!

Best regards,
{NAME}
{BUSINESS_NAME}',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Gamification & Incentives
    |--------------------------------------------------------------------------
    |
    | Reward tiers and bonuses for top referrers.
    |
    */
    'gamification' => [
        'enabled' => (bool) env('REFERRAL_GAMIFICATION_ENABLED', true),
        
        // Leaderboard
        'leaderboard_enabled' => true,
        'leaderboard_period' => 'monthly', // 'weekly', 'monthly', 'yearly', 'all-time'
        'leaderboard_top_count' => 10, // Show top 10
        
        // Referral tiers (bonus credits for reaching milestones)
        'tiers' => [
            'bronze' => [
                'name' => 'Bronze Ambassador',
                'referrals_required' => 5,
                'bonus_credits' => 250, // Business only
                'bonus_commission_rate' => 0, // Customer: no bonus
                'badge_icon' => '🥉',
            ],
            'silver' => [
                'name' => 'Silver Ambassador',
                'referrals_required' => 15,
                'bonus_credits' => 1000,
                'bonus_commission_rate' => 0.02, // Customer: +2% commission (12% total)
                'badge_icon' => '🥈',
            ],
            'gold' => [
                'name' => 'Gold Ambassador',
                'referrals_required' => 50,
                'bonus_credits' => 5000,
                'bonus_commission_rate' => 0.05, // Customer: +5% commission (15% total)
                'badge_icon' => '🥇',
            ],
            'platinum' => [
                'name' => 'Platinum Ambassador',
                'referrals_required' => 100,
                'bonus_credits' => 15000,
                'bonus_commission_rate' => 0.10, // Customer: +10% commission (20% total)
                'badge_icon' => '💎',
            ],
        ],
        
        // Special campaigns (time-limited bonuses)
        'campaigns' => [
            'enabled' => false,
            // Define campaigns in database rather than config
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | When to send referral-related notifications.
    |
    */
    'notifications' => [
        'enabled' => true,
        
        // Customer notifications
        'customer' => [
            'referral_signup' => true, // When someone uses your code
            'referral_qualified' => true, // When referral becomes qualified
            'commission_earned' => true, // When you earn commission
            'withdrawal_processed' => true, // When withdrawal is processed
            'tier_achieved' => true, // When you reach a new tier
        ],
        
        // Business notifications
        'business' => [
            'referral_signup' => true, // When someone uses your code
            'referral_qualified' => true, // When referral becomes qualified
            'credits_earned' => true, // When you earn credits
            'credits_converted' => true, // When credits are converted
            'credits_expiring' => true, // When credits are about to expire
            'tier_achieved' => true, // When you reach a new tier
        ],
        
        // Admin notifications
        'admin' => [
            'suspicious_activity' => true, // Fraud alerts
            'large_withdrawal' => true, // Withdrawals over threshold
            'withdrawal_threshold' => 100000, // ₦100,000
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing & Development
    |--------------------------------------------------------------------------
    |
    | Settings for testing the referral system.
    |
    */
    'testing' => [
        'enabled' => (bool) env('REFERRAL_TESTING_MODE', false),
        'test_emails' => env('REFERRAL_TEST_EMAILS', ''), // Comma-separated
        'bypass_fraud_detection' => false, // Never bypass in production!
        'instant_qualification' => false, // Skip qualification criteria
        'instant_withdrawal' => false, // Skip withdrawal approval
    ],

];