<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WithdrawalRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'amount',
        'bank_name',
        'account_name',
        'account_number',
        'sort_code',
        'status',
        'processed_by',
        'processed_at',
        'rejection_reason',
        'transaction_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeProcessed($query)
    {
        return $query->whereIn('status', ['approved', 'rejected']);
    }

    // Helper methods
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
