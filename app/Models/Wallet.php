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
        'business_id',
        'balance',
        'currency',
        'ad_credits',
        'quote_credits',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
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
            'business_id' => $this->business_id,
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

        $ref = $reference ?? $this;
        $balanceBefore = $this->balance;
        $this->decrement('balance', $amount);
        $this->refresh();

        return WalletTransaction::create([
            'wallet_id' => $this->id,
            'business_id' => $this->business_id,
            'user_id' => $this->user_id,
            'type' => 'withdrawal',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description ?? 'Wallet withdrawal',
            'reference_type' => get_class($ref),
            'reference_id' => $ref->id,
        ]);
    }

    /**
     * @param  float  $amount
     * @param  string  $description
     * @param  mixed  $reference
     * @param  int|null  $credits  When provided (e.g. credit purchase), store credits/credits_before/credits_after.
     */
    public function purchase($amount, $description, $reference = null, $credits = null)
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient wallet balance');
        }

        $ref = $reference ?? $this;
        $balanceBefore = $this->balance;
        $creditsBefore = $credits !== null ? (int) $this->ad_credits : 0;
        
        // Deduct balance
        $this->decrement('balance', $amount);
        
        // If credits are provided, increment ad_credits
        if ($credits !== null && $credits > 0) {
            $this->increment('ad_credits', $credits);
        }
        
        $this->refresh();

        $payload = [
            'wallet_id' => $this->id,
            'business_id' => $this->business_id,
            'user_id' => $this->user_id,
            'type' => 'purchase',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description,
            'reference_type' => get_class($ref),
            'reference_id' => $ref->id,
        ];

        if ($credits !== null && $credits > 0) {
            $payload['credits'] = $credits;
            $payload['credits_before'] = $creditsBefore;
            $payload['credits_after'] = $this->ad_credits;
        }

        return WalletTransaction::create($payload);
    }

    /**
     * @param  int  $credits
     * @param  string|null  $description
     * @param  mixed  $reference  Transaction (gateway/bank) or null
     * @param  float|null  $amount  Cash paid (e.g. gateway/bank transfer). Omit for wallet-funded.
     */
    public function addCredits($credits, $description = null, $reference = null, $amount = null)
    {
        $ref = $reference ?? $this;
        $creditsBefore = $this->ad_credits;
        $balance = (float) $this->balance; // unchanged for credit-only ops
        $this->increment('ad_credits', $credits);
        $this->refresh();

        return WalletTransaction::create([
            'wallet_id' => $this->id,
            'business_id' => $this->business_id,
            'user_id' => $this->user_id,
            'type' => 'credit_purchase',
            'amount' => $amount !== null ? (float) $amount : 0,
            'credits' => $credits,
            'credits_before' => $creditsBefore,
            'credits_after' => $this->ad_credits,
            'balance_before' => $balance,
            'balance_after' => $balance,
            'description' => $description ?? 'Ad credits added',
            'reference_type' => get_class($ref),
            'reference_id' => $ref->id,
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
            'business_id' => $this->business_id,
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

    /**
     * Add quote credits to wallet
     * @param  int  $credits
     * @param  string|null  $description
     * @param  mixed  $reference  Transaction (gateway/bank) or null
     * @param  float|null  $amount  Cash paid (e.g. gateway/bank transfer). Omit for wallet-funded.
     */
    public function addQuoteCredits($credits, $description = null, $reference = null, $amount = null)
    {
        $ref = $reference ?? $this;
        $creditsBefore = $this->quote_credits;
        $balance = (float) $this->balance; // unchanged for credit-only ops
        $this->increment('quote_credits', $credits);
        $this->refresh();

        return WalletTransaction::create([
            'wallet_id' => $this->id,
            'business_id' => $this->business_id,
            'user_id' => $this->user_id,
            'type' => 'quote_purchase',
            'amount' => $amount !== null ? (float) $amount : 0,
            'quote_credits' => $credits,
            'quote_credits_before' => $creditsBefore,
            'quote_credits_after' => $this->quote_credits,
            'balance_before' => $balance,
            'balance_after' => $balance,
            'description' => $description ?? 'Quote credits added',
            'reference_type' => get_class($ref),
            'reference_id' => $ref->id,
        ]);
    }

    /**
     * Use quote credits (deduct 1 credit for quote submission)
     * @param  string  $description
     * @param  mixed  $reference  QuoteResponse or null
     */
    public function useQuoteCredit($description, $reference = null)
    {
        if ($this->quote_credits < 1) {
            throw new \Exception('Insufficient quote credits');
        }

        $ref = $reference ?? $this;
        $creditsBefore = $this->quote_credits;
        $balance = (float) $this->balance; // unchanged for credit-only ops
        $this->decrement('quote_credits', 1);
        $this->refresh();

        return WalletTransaction::create([
            'wallet_id' => $this->id,
            'business_id' => $this->business_id,
            'user_id' => $this->user_id,
            'type' => 'quote_submission',
            'amount' => 0,
            'quote_credits' => 1,
            'quote_credits_before' => $creditsBefore,
            'quote_credits_after' => $this->quote_credits,
            'balance_before' => $balance,
            'balance_after' => $balance,
            'description' => $description,
            'reference_type' => get_class($ref),
            'reference_id' => $ref->id,
        ]);
    }

    public function hasQuoteCredits($credits = 1)
    {
        return $this->quote_credits >= $credits;
    }
}