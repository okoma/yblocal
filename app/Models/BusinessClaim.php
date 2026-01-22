<?php

// ============================================
// app/Models/BusinessClaim.php
// Claim request workflow
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessClaim extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'user_id',
        'claim_message',
        'claimant_position',
        'verification_phone',
        'verification_email',
        'phone_verified',
        'email_verified',
        'status',
        'reviewed_by',
        'rejection_reason',
        'admin_notes',
        'reviewed_at',
        'approved_at',
    ];

    protected $casts = [
        'phone_verified' => 'boolean',
        'email_verified' => 'boolean',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
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

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDocumentsSubmitted($query)
    {
        return $query->where('status', 'documents_submitted');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeDisputed($query)
    {
        return $query->where('status', 'disputed');
    }

    // Helper methods
    public function approve($adminId)
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
            'approved_at' => now(),
        ]);

        // Update business - TRANSFER OWNERSHIP
        $this->business->update([
            'user_id' => $this->user_id, // Transfer ownership to claimant
            'is_claimed' => true,
            'claimed_by' => $this->user_id,
            'claimed_at' => now(),
        ]);
    }

    public function reject($adminId, $reason)
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isUnderReview()
    {
        return $this->status === 'under_review';
    }

    public function isDisputed()
    {
        return $this->status === 'disputed';
    }

    // Check if user already has a pending/approved claim for this business
    public static function hasExistingClaim($userId, $businessId)
    {
        return static::where('user_id', $userId)
            ->where('business_id', $businessId)
            ->whereIn('status', ['pending', 'under_review', 'approved'])
            ->exists();
    }

    // Check if business has any pending/approved claims
    public static function businessHasPendingClaims($businessId)
    {
        return static::where('business_id', $businessId)
            ->whereIn('status', ['pending', 'under_review', 'approved'])
            ->exists();
    }
}