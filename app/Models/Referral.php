<?php

// ============================================
// app/Models/Referral.php
// Referral program
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'referral_code',
        'referrer_reward',
        'referrer_credits',
        'referred_reward',
        'referred_credits',
        'status',
        'rewards_paid',
        'completed_at',
    ];

    protected $casts = [
        'referrer_reward' => 'decimal:2',
        'referred_reward' => 'decimal:2',
        'rewards_paid' => 'boolean',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Helper methods
    public function complete()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        if (!$this->rewards_paid) {
            $this->payRewards();
        }
    }

    public function payRewards()
    {
        // Get wallets for referrer and referred (using their first business)
        // Wallets are now business-scoped, so we get the first business wallet
        $referrerWallet = $this->getUserWallet($this->referrer);
        $referredWallet = $this->getUserWallet($this->referred);
        
        // Pay referrer
        if ($this->referrer_reward > 0 && $referrerWallet) {
            $referrerWallet->deposit(
                $this->referrer_reward,
                'Referral reward for referring ' . $this->referred->name,
                $this
            );
        }

        if ($this->referrer_credits > 0 && $referrerWallet) {
            $referrerWallet->addCredits(
                $this->referrer_credits,
                'Referral credits for referring ' . $this->referred->name,
                $this
            );
        }

        // Pay referred
        if ($this->referred_reward > 0 && $referredWallet) {
            $referredWallet->deposit(
                $this->referred_reward,
                'Welcome bonus from referral',
                $this
            );
        }

        if ($this->referred_credits > 0 && $referredWallet) {
            $referredWallet->addCredits(
                $this->referred_credits,
                'Welcome bonus credits',
                $this
            );
        }

        $this->update(['rewards_paid' => true]);
    }
    
    /**
     * Get wallet for user's first business (or create if needed)
     */
    protected function getUserWallet(User $user): ?Wallet
    {
        // Get user's first business (owned or managed)
        $business = $user->businesses()->first() ?? $user->managedBusinesses()->first();
        
        if (!$business) {
            // User has no business yet - can't create wallet without business
            \Illuminate\Support\Facades\Log::warning('Cannot pay referral reward: user has no business', [
                'user_id' => $user->id,
                'referral_id' => $this->id,
            ]);
            return null;
        }
        
        // Get or create wallet for the business
        return Wallet::firstOrCreate(
            ['business_id' => $business->id],
            [
                'user_id' => $user->id,
                'balance' => 0,
                'currency' => 'NGN',
                'ad_credits' => 0,
            ]
        );
    }
}
