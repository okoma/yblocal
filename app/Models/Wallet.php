<?php
// ============================================
// app/Models/Wallet.php
// User wallet for ad credits and cash
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'currency',
        'ad_credits',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Wallet activity log - All wallet activities (deposit, withdrawal, purchase)
     * Includes balance before/after for complete audit trail
     */
    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Payment gateway transactions - For wallet funding via Paystack, Flutterwave, etc.
     * Links to the unified payment system
     */
    public function paymentTransactions()
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    /**
     * Withdrawal requests for this wallet
     */
    public function withdrawalRequests()
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    // Helper methods
    public function deposit($amount, $description = null, $reference = null)
    {
        $ref = $reference ?? $this;
        $balanceBefore = $this->balance;
        $this->increment('balance', $amount);
        $this->refresh();

        return WalletTransaction::create([
            'wallet_id' => $this->id,
            'user_id' => $this->user_id,
            'type' => 'deposit',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'Wallet deposit',
            'reference_type' => get_class($ref),
            'reference_id' => $ref->id,
        ]);
    }

    public function withdraw($amount, $description = null, $reference = null)
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient wallet balance');
        }

        $balanceBefore = $this->balance;
        $this->decrement('balance', $amount);
        $this->refresh();

        return WalletTransaction::create([
            'wallet_id' => $this->id,
            'user_id' => $this->user_id,
            'type' => 'withdrawal',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'Wallet withdrawal',
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
        ]);
    }

    public function purchase($amount, $description, $reference = null)
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient wallet balance');
        }

        $ref = $reference ?? $this;
        $balanceBefore = $this->balance;
        $this->decrement('balance', $amount);
        $this->refresh();

        return WalletTransaction::create([
            'wallet_id' => $this->id,
            'user_id' => $this->user_id,
            'type' => 'purchase',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description,
            'reference_type' => get_class($ref),
            'reference_id' => $ref->id,
        ]);
    }

    /**
     * @param  int  $credits
     * @param  string|null  $description
     * @param  mixed  $reference  Transaction (gateway/bank) or null
     * @param  float|null  $amount  Cash paid (e.g. gateway/bank transfer). Omit for wallet-funded.
     */
    public function addCredits($credits, $description = null, $reference = null, $amount = null)
    {
        $creditsBefore = $this->ad_credits;
        $balance = (float) $this->balance; // unchanged for credit-only ops
        $this->increment('ad_credits', $credits);
        $this->refresh();

        return WalletTransaction::create([
            'wallet_id' => $this->id,
            'user_id' => $this->user_id,
            'type' => 'credit_purchase',
            'amount' => $amount !== null ? (float) $amount : 0,
            'credits' => $credits,
            'credits_before' => $creditsBefore,
            'credits_after' => $this->ad_credits,
            'balance_before' => $balance,
            'balance_after' => $balance,
            'description' => $description ?? 'Ad credits added',
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
        ]);
    }

    public function useCredits($credits, $description, $reference = null)
    {
        if ($this->ad_credits < $credits) {
            throw new \Exception('Insufficient ad credits');
        }

        $ref = $reference ?? $this;
        $creditsBefore = $this->ad_credits;
        $balance = (float) $this->balance; // unchanged for credit-only ops
        $this->decrement('ad_credits', $credits);
        $this->refresh();

        return WalletTransaction::create([
            'wallet_id' => $this->id,
            'user_id' => $this->user_id,
            'type' => 'credit_usage',
            'credits' => $credits,
            'credits_before' => $creditsBefore,
            'credits_after' => $this->ad_credits,
            'balance_before' => $balance,
            'balance_after' => $balance,
            'description' => $description,
            'reference_type' => get_class($ref),
            'reference_id' => $ref->id,
        ]);
    }

    public function hasBalance($amount)
    {
        return $this->balance >= $amount;
    }

    public function hasCredits($credits)
    {
        return $this->ad_credits >= $credits;
    }
}