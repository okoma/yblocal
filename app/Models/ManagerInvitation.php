<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ManagerInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'invited_by',
        'email',
        'invitation_token',
        'position',
        'permissions',
        'status',
        'expires_at',
        'accepted_at',
        'user_id',
    ];

    protected $casts = [
        'permissions' => 'array',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    /**
     * Business this invitation is for
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * User who sent the invitation
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * User who accepted the invitation (if accepted)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ============================================
    // STATIC METHODS
    // ============================================

    /**
     * Create a new manager invitation
     */
    public static function createInvitation(
        int $businessId,
        int $invitedBy,
        string $email,
        string $position = 'Business Manager',
        ?array $permissions = null,
        int $expiresInDays = 7
    ): self {
        $defaultPermissions = [
            'can_edit_business' => false,
            'can_manage_products' => true,
            'can_respond_to_reviews' => true,
            'can_view_leads' => true,
            'can_respond_to_leads' => true,
            'can_view_analytics' => true,
            'can_access_financials' => false,
            'can_manage_staff' => false,
        ];

        return self::create([
            'business_id' => $businessId,
            'invited_by' => $invitedBy,
            'email' => $email,
            'invitation_token' => Str::random(64),
            'position' => $position,
            'permissions' => $permissions ?? $defaultPermissions,
            'status' => 'pending',
            'expires_at' => now()->addDays($expiresInDays),
        ]);
    }

    // ============================================
    // INSTANCE METHODS
    // ============================================

    /**
     * Accept the invitation and create business manager relationship
     */
    public function accept(int $userId): BusinessManager
    {
        if ($this->status !== 'pending') {
            throw new \Exception('Invitation is not pending');
        }

        if ($this->expires_at->isPast()) {
            $this->update(['status' => 'expired']);
            throw new \Exception('Invitation has expired');
        }

        // Check if user already manages this business
        $existingManager = BusinessManager::where('business_id', $this->business_id)
            ->where('user_id', $userId)
            ->first();

        if ($existingManager) {
            // Update existing manager
            $existingManager->update([
                'is_active' => true,
                'permissions' => $this->permissions,
                'position' => $this->position,
            ]);
        } else {
            // Create new business manager
            $existingManager = BusinessManager::create([
                'business_id' => $this->business_id,
                'user_id' => $userId,
                'position' => $this->position,
                'permissions' => $this->permissions,
                'is_active' => true,
                'joined_at' => now(),
            ]);
        }

        // Update invitation
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
            'user_id' => $userId,
        ]);

        return $existingManager;
    }

    /**
     * Decline the invitation
     */
    public function decline(): void
    {
        if ($this->status !== 'pending') {
            throw new \Exception('Invitation is not pending');
        }

        $this->update(['status' => 'declined']);
    }

    /**
     * Check if invitation is valid (pending and not expired)
     */
    public function isValid(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isFuture();
    }

    /**
     * Mark invitation as expired
     */
    public function markAsExpired(): void
    {
        if ($this->status === 'pending' && $this->expires_at->isPast()) {
            $this->update(['status' => 'expired']);
        }
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '<=', now());
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    // ============================================
    // BOOT METHOD
    // ============================================

    protected static function booted(): void
    {
        // Auto-generate token if not provided
        static::creating(function ($invitation) {
            if (empty($invitation->invitation_token)) {
                $invitation->invitation_token = Str::random(64);
            }
        });
    }
}
