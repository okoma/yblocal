<?php

// ============================================
// app/Models/WalletTransaction.php
// Track all wallet movements
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'business_id',
        'user_id',
        'type',
        'amount',
        'credits',
        'balance_before',
        'balance_after',
        'credits_before',
        'credits_after',
        'quote_credits',
        'quote_credits_before',
        'quote_credits_after',
        'description',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    // Relationships
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeDeposits($query)
    {
        return $query->where('type', 'deposit');
    }

    public function scopeWithdrawals($query)
    {
        return $query->where('type', 'withdrawal');
    }

    public function scopePurchases($query)
    {
        return $query->where('type', 'purchase');
    }

    public function scopeCreditTransactions($query)
    {
        return $query->whereIn('type', ['credit_purchase', 'credit_usage']);
    }

    public function scopeQuoteTransactions($query)
    {
        return $query->whereIn('type', ['quote_purchase', 'quote_submission']);
    }
}
