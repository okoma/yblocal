<?php

// ============================================
// app/Models/Subscription.php
// Business subscriptions - Each subscription is linked to a specific business
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'user_id',
        'subscription_plan_id',
        'billing_interval',
        'subscription_code',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
        'paused_at',
        'auto_renew',
        'cancellation_reason',
        'faqs_used',
        'leads_viewed_used',
        'monthly_leads_viewed',
        'last_leads_reset_at',
        'products_used',
        'team_members_used',
        'photos_used',
        'ad_credits_used',
        'payment_method',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'paused_at' => 'datetime',
        'last_leads_reset_at' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    // Relationships
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('ends_at', '>', now());
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('status', 'active')
            ->whereBetween('ends_at', [now(), now()->addDays($days)]);
    }

    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<=', now());
    }

    // Helper methods
    public function isActive()
    {
        return $this->status === 'active' && $this->ends_at && $this->ends_at->isFuture();
    }

    public function isTrialing()
    {
        return $this->status === 'trialing' && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isExpired()
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function daysRemaining()
    {
        if (!$this->ends_at) {
            return 0;
        }
        
        // Use diffInDays with absolute=false to get decimal, then ceil to round up to whole days
        $days = now()->diffInDays($this->ends_at, false);
        
        // Round up to nearest whole day (so 363.75 becomes 364)
        return max(0, (int) ceil($days));
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false,
        ]);
    }

    public function pause()
    {
        $this->update([
            'status' => 'paused',
            'paused_at' => now(),
        ]);
    }

    public function resume()
    {
        $this->update([
            'status' => 'active',
            'paused_at' => null,
        ]);
    }

    public function renew()
    {
        // Renew based on billing interval
        $duration = $this->billing_interval === 'yearly' ? 365 : 30;
        
        // If subscription has expired, extend from today
        // Otherwise, extend from the current end date
        $startDate = $this->ends_at && $this->ends_at->isFuture() 
            ? $this->ends_at 
            : now();
        
        $this->update([
            'ends_at' => $startDate->copy()->addDays($duration),
            'status' => 'active', // Ensure status is active after renewal
        ]);
    }
    
    public function getPrice()
    {
        return $this->billing_interval === 'yearly' 
            ? $this->plan->yearly_price 
            : $this->plan->price;
    }
    
    public function isYearly()
    {
        return $this->billing_interval === 'yearly';
    }
    
    public function isMonthly()
    {
        return $this->billing_interval === 'monthly';
    }

    public function canAddFaq()
    {
        return $this->plan->max_faqs === null || $this->faqs_used < $this->plan->max_faqs;
    }

    public function canViewMoreLeads()
    {
        // Check if reset is needed first
        $this->checkAndResetMonthlyLeads();
        
        // They can always receive unlimited leads, but viewing is limited monthly
        return $this->plan->max_leads_view === null || $this->monthly_leads_viewed < $this->plan->max_leads_view;
    }
    
    /**
     * Check if monthly leads counter needs to be reset
     * - Yearly subscriptions: Reset on calendar month (1st of month)
     * - Monthly subscriptions: Reset on billing cycle (subscription renewal date)
     */
    public function checkAndResetMonthlyLeads(): void
    {
        $shouldReset = false;
        
        if ($this->billing_interval === 'yearly') {
            // Reset on calendar month (1st of each month)
            $lastReset = $this->last_leads_reset_at;
            
            if (!$lastReset || $lastReset->month !== now()->month || $lastReset->year !== now()->year) {
                $shouldReset = true;
            }
        } elseif ($this->billing_interval === 'monthly') {
            // Reset on billing cycle (based on starts_at date)
            $lastReset = $this->last_leads_reset_at ?? $this->starts_at;
            $dayOfMonth = $this->starts_at->day;
            
            // Calculate next reset date
            $nextResetDate = $lastReset->copy()->addMonth()->startOfDay();
            
            if (now()->gte($nextResetDate)) {
                $shouldReset = true;
            }
        }
        
        if ($shouldReset) {
            $this->resetMonthlyLeads();
        }
    }
    
    /**
     * Reset monthly leads counter
     */
    public function resetMonthlyLeads(): void
    {
        $this->update([
            'monthly_leads_viewed' => 0,
            'last_leads_reset_at' => now(),
        ]);
    }
    
    /**
     * Increment monthly leads viewed counter
     */
    public function incrementLeadsViewed(): void
    {
        $this->checkAndResetMonthlyLeads();
        $this->increment('monthly_leads_viewed');
        $this->increment('leads_viewed_used'); // Keep total count too
    }
    
    /**
     * Get remaining leads that can be viewed this month
     */
    public function getRemainingLeadsView(): ?int
    {
        $this->checkAndResetMonthlyLeads();
        
        if ($this->plan->max_leads_view === null) {
            return null; // Unlimited
        }
        
        return max(0, $this->plan->max_leads_view - $this->monthly_leads_viewed);
    }

    public function canAddProduct()
    {
        return $this->plan->max_products === null || $this->products_used < $this->plan->max_products;
    }
}