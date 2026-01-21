<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessManager extends Pivot
{
    protected $table = 'business_managers';

    protected $fillable = [
        'business_id',
        'user_id',
        'position',
        'permissions',
        'is_active',
        'is_primary',
        'joined_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'joined_at' => 'datetime',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    /**
     * Business being managed
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * User who is the manager
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ============================================
    // PERMISSION METHODS
    // ============================================

    /**
     * Check if manager has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        return $permissions[$permission] ?? false;
    }

    /**
     * Grant a permission
     */
    public function grantPermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        $permissions[$permission] = true;
        $this->update(['permissions' => $permissions]);
    }

    /**
     * Revoke a permission
     */
    public function revokePermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        $permissions[$permission] = false;
        $this->update(['permissions' => $permissions]);
    }

    /**
     * Update multiple permissions at once
     */
    public function updatePermissions(array $permissions): void
    {
        $this->update(['permissions' => $permissions]);
    }

    /**
     * Get all granted permissions
     */
    public function getGrantedPermissions(): array
    {
        $permissions = $this->permissions ?? [];
        return array_keys(array_filter($permissions));
    }

    // ============================================
    // STATUS METHODS
    // ============================================

    /**
     * Activate the manager
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the manager
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Set as primary manager
     */
    public function setAsPrimary(): void
    {
        // Remove primary flag from other managers of this business
        static::where('business_id', $this->business_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->update(['is_primary' => true]);
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
