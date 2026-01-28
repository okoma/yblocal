<?php

// ============================================
// app/Models/QuoteRequest.php
// Quote requests created by customers
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuoteRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'category_id',
        'state_location_id',
        'city_location_id',
        'title',
        'description',
        'budget_min',
        'budget_max',
        'status',
        'expires_at',
        'attachments',
    ];

    protected $casts = [
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'expires_at' => 'datetime',
        'attachments' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stateLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'state_location_id');
    }

    public function cityLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'city_location_id');
    }
    
    /**
     * Get the effective location (city if set, otherwise state)
     */
    public function getEffectiveLocationAttribute()
    {
        return $this->cityLocation ?? $this->stateLocation;
    }

    public function responses(): HasMany
    {
        return $this->hasMany(QuoteResponse::class);
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'open')
            ->where('expires_at', '<=', now());
    }

    // Helper methods
    public function isExpired(): bool
    {
        return $this->status === 'open' 
            && $this->expires_at 
            && $this->expires_at->isPast();
    }

    public function markAsExpired(): void
    {
        if ($this->status === 'open') {
            $this->update(['status' => 'expired']);
        }
    }

    public function getResponseCount(): int
    {
        return $this->responses()->count();
    }
}
