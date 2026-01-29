<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Audit trail for business referral credits (earned, converted to ads/quote/subscription).
 */
class BusinessReferralCreditTransaction extends Model
{
    use HasFactory;

    public const TYPE_EARNED = 'earned';
    public const TYPE_CONVERTED_AD_CREDITS = 'converted_to_ad_credits';
    public const TYPE_CONVERTED_QUOTE_CREDITS = 'converted_to_quote_credits';
    public const TYPE_CONVERTED_SUBSCRIPTION = 'converted_to_subscription';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'business_id',
        'business_referral_id',
        'amount',
        'type',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function businessReferral(): BelongsTo
    {
        return $this->belongsTo(BusinessReferral::class, 'business_referral_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }
}
