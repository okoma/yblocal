<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Customer referral commission balance (cash; withdrawable).
 */
class CustomerReferralWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'currency',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CustomerReferralTransaction::class, 'customer_referral_wallet_id');
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(CustomerReferralWithdrawal::class, 'customer_referral_wallet_id');
    }

    /**
     * @param  float  $amount
     * @param  string|null  $description
     * @param  mixed  $reference  Polymorphic reference (e.g. Transaction)
     * @param  string  $type  'commission' or 'adjustment'
     * @param  int|null  $customerReferralId
     * @param  int|null  $transactionId  Source payment transaction
     */
    public function deposit(
        float $amount,
        ?string $description = null,
        $reference = null,
        string $type = 'commission',
        ?int $customerReferralId = null,
        ?int $transactionId = null
    ): CustomerReferralTransaction {
        $ref = $reference ?? $this;
        $balanceBefore = (float) $this->balance;
        $this->increment('balance', $amount);
        $this->refresh();

        $data = [
            'customer_referral_wallet_id' => $this->id,
            'amount' => $amount,
            'type' => $type,
            'balance_before' => $balanceBefore,
            'balance_after' => (float) $this->balance,
            'description' => $description ?? 'Commission credited',
            'reference_type' => get_class($ref),
            'reference_id' => $ref->id,
        ];
        if ($customerReferralId !== null) {
            $data['customer_referral_id'] = $customerReferralId;
        }
        if ($transactionId !== null) {
            $data['transaction_id'] = $transactionId;
        }

        return CustomerReferralTransaction::create($data);
    }

    public function withdraw(float $amount, ?string $description = null, $reference = null): CustomerReferralTransaction
    {
        if ((float) $this->balance < $amount) {
            throw new \Exception('Insufficient referral balance');
        }

        $ref = $reference ?? $this;
        $balanceBefore = (float) $this->balance;
        $this->decrement('balance', $amount);
        $this->refresh();

        return CustomerReferralTransaction::create([
            'customer_referral_wallet_id' => $this->id,
            'amount' => -$amount,
            'type' => 'withdrawal',
            'balance_before' => $balanceBefore,
            'balance_after' => (float) $this->balance,
            'description' => $description ?? 'Withdrawal',
            'reference_type' => get_class($ref),
            'reference_id' => $ref->id,
        ]);
    }

    public function hasBalance(float $amount): bool
    {
        return (float) $this->balance >= $amount;
    }
}
