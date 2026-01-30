<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Business → Business referral.
 * Referring business earns referral credits when referred business signs up.
 */
class BusinessReferral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_business_id',
        'referred_business_id',
        'referral_code',
        'referral_credits_awarded',
        'status',
        'ip_address',
        'device_fingerprint',
        'user_agent',
        'is_suspicious',
        'fraud_notes',
        'verified_at',
    ];

    protected $casts = [
        'referral_credits_awarded' => 'integer',
        'is_suspicious' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeCredited(Builder $query): Builder
    {
        return $query->where('status', 'credited');
    }

    public function referrerBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'referrer_business_id');
    }

    public function referredBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'referred_business_id');
    }
}
