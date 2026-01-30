<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Customer → Business referral.
 * When the referred business makes a payment, the customer earns 10% commission.
 */
class CustomerReferral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_user_id',
        'referred_business_id',
        'referral_code',
        'status',
        'ip_address',
        'device_fingerprint',
        'user_agent',
        'is_suspicious',
        'fraud_notes',
        'verified_at',
    ];

    protected $casts = [
        'is_suspicious' => 'boolean',
        'verified_at' => 'datetime',
    ];

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeQualified($query)
    {
        return $query->where('status', 'qualified');
    }

    // Relationships
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    public function referredBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'referred_business_id');
    }

    // Helpers
    public function getReferredBusinessNameAttribute(): string
    {
        return $this->referredBusiness?->business_name ?? '—';
    }
}
