<?php

// ============================================
// app/Models/Subscription.php
// Business subscription (subscriptions belong to businesses, not users)
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
        return $this->status === 'active' && $this->ends_at->isFuture();
    }

    public function isTrialing()
    {
        return $this->status === 'trialing' && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isExpired()
    {
        return $this->ends_at->isPast();
    }

    public function daysRemaining()
    {
        return max(0, now()->diffInDays($this->ends_at, false));
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
        
        $this->update([
            'ends_at' => $this->ends_at->addDays($duration),
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
        // They can always receive unlimited leads, but viewing is limited
        return $this->plan->max_leads_view === null || $this->leads_viewed_used < $this->plan->max_leads_view;
    }

    public function canAddProduct()
    {
        return $this->plan->max_products === null || $this->products_used < $this->plan->max_products;
    }
}