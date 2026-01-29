<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Audit trail for customer referral wallet (commission, withdrawal, adjustment).
 */
class CustomerReferralTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_referral_wallet_id',
        'customer_referral_id',
        'transaction_id',
        'amount',
        'type',
        'balance_before',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function customerReferralWallet(): BelongsTo
    {
        return $this->belongsTo(CustomerReferralWallet::class, 'customer_referral_wallet_id');
    }

    public function customerReferral(): BelongsTo
    {
        return $this->belongsTo(CustomerReferral::class, 'customer_referral_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }
}
