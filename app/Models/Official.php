<?php
// ============================================
// app/Models/Official.php
// Business team members/staff with social accounts
// Supports both standalone businesses AND branches
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class Official extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',           // Business relationship
        'name',
        'position',
        'photo',
        'social_accounts',       // JSON: {"linkedin": "url", "twitter": "url", etc}
        'is_active',
        'order',
    ];

    protected $casts = [
        'social_accounts' => 'array',
        'is_active' => 'boolean',
    ];

    // ============================================
    // Relationships
    // ============================================

    /**
     * Official belongs to a Business (for standalone businesses)
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    // ============================================
    // Helper Methods
    // ============================================

    /**
     * Get the parent business
     */
    public function parent()
    {
        return $this->business;
    }

    /**
     * Check if official belongs to business
     */
    public function isBusinessOfficial(): bool
    {
        return !is_null($this->business_id);
    }

    /**
     * Get social media link for a specific platform
     */
    public function getSocialLink(string $platform): ?string
    {
        if (!$this->social_accounts) {
            return null;
        }
        
        return $this->social_accounts[$platform] ?? null;
    }

    /**
     * Check if official has a social account on specific platform
     */
    public function hasSocial(string $platform): bool
    {
        return !empty($this->getSocialLink($platform));
    }

    /**
     * Get all active social accounts
     */
    public function getActiveSocialAccounts(): array
    {
        if (!$this->social_accounts) {
            return [];
        }

        return array_filter($this->social_accounts, fn($url) => !empty($url));
    }

    /**
     * Get count of social accounts
     */
    public function getSocialAccountsCount(): int
    {
        return count($this->getActiveSocialAccounts());
    }

    /**
     * Get parent business name
     */
    public function getParentName(): string
    {
        return $this->business?->business_name ?? 'N/A';
    }

    // ============================================
    // Scopes
    // ============================================

    /**
     * Scope for active officials
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for officials of a specific business
     */
    public function scopeForBusiness(Builder $query, int $businessId): Builder
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for officials of a specific branch
     */
    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('business_branch_id', $branchId);
    }

    /**
     * Scope ordered by display order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order', 'asc')->orderBy('name', 'asc');
    }

    // ============================================
    // Validation & Cleanup
    // ============================================

    /**
     * Boot method to ensure official belongs to either business OR branch
     */
    protected static function booted()
    {
        static::creating(function ($official) {
            // Ensure official belongs to a business
            if (!$official->business_id) {
                throw new \InvalidArgumentException('Official must belong to a business.');
            }
        });

        static::deleting(function ($official) {
            // Clean up photo file when deleting official
            if ($official->photo) {
                Storage::disk('public')->delete($official->photo);
            }
        });
    }
}