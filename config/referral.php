<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Customer referral commission (Customer → Business)
    |--------------------------------------------------------------------------
    |
    | When a referred business pays (subscription, ad credits, quote credits,
    | wallet funding), the referring customer earns this percentage as cash
    | commission in their referral wallet.
    |
    */
    'customer_commission_rate' => (float) env('REFERRAL_CUSTOMER_COMMISSION_RATE', 0.10),

    /*
    |--------------------------------------------------------------------------
    | Business referral credits (Business → Business)
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
    | Business referral credit conversion
    |--------------------------------------------------------------------------
    |
    | Conversion is 1:1 for ad credits and quote credits.
    | Credits required to redeem 1 month of subscription (extends current or free plan).
    |
    */
    'conversion_to_subscription_credits' => (int) env('REFERRAL_CONVERSION_TO_SUBSCRIPTION_CREDITS', 500),

    /*
    |--------------------------------------------------------------------------
    | Referral link base URLs (for share links)
    |--------------------------------------------------------------------------
    |
    | Business register URL: used for both customer and business referral links.
    | Append ?ref=CODE (customer's user.referral_code or business's referral_code).
    |
    */
    'business_register_url' => rtrim(env('REFERRAL_BUSINESS_REGISTER_URL', 'https://biz.yellowbooks.ng/register'), '/'),

];
