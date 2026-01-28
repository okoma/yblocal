<?php

// ============================================
// app/Models/QuoteResponse.php
// Quote responses submitted by businesses
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_request_id',
        'business_id',
        'price',
        'delivery_time',
        'message',
        'status',
        'attachments',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'attachments' => 'array',
    ];

    // Relationships
    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    // Scopes
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeShortlisted($query)
    {
        return $query->where('status', 'shortlisted');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    // Helper methods
    public function shortlist(): void
    {
        $this->update(['status' => 'shortlisted']);
    }

    public function accept(): void
    {
        $this->update(['status' => 'accepted']);
        
        // Update quote request status to accepted
        $this->quoteRequest->update(['status' => 'accepted']);
        
        // Reject all other responses for this request
        $this->quoteRequest->responses()
            ->where('id', '!=', $this->id)
            ->update(['status' => 'rejected']);
    }

    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }
}
