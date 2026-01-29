<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Customer referral commission withdrawal request.
 */
class CustomerReferralWithdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_referral_wallet_id',
        'amount',
        'bank_name',
        'account_name',
        'account_number',
        'sort_code',
        'status',
        'processed_by',
        'processed_at',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customerReferralWallet(): BelongsTo
    {
        return $this->belongsTo(CustomerReferralWallet::class, 'customer_referral_wallet_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function approve(User $processor, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'processed_by' => $processor->id,
            'processed_at' => now(),
            'notes' => $notes,
        ]);
    }

    public function reject(User $processor, string $reason, ?string $notes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'processed_by' => $processor->id,
            'processed_at' => now(),
            'rejection_reason' => $reason,
            'notes' => $notes,
        ]);
    }
}
