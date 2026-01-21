<?php
// app/Models/SavedBusiness.php - COMPLETE FIXED VERSION

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedBusiness extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',           // Business relationship
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    /**
     * User who saved the business/branch
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Standalone Business (if saved)
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Get the parent business
     */
    public function parent()
    {
        return $this->business;
    }

    /**
     * Check if saved item is a business
     */
    public function isForBusiness(): bool
    {
        return !is_null($this->business_id);
    }

    /**
     * Toggle save status (save/unsave)
     * 
     * @param int $userId User ID
     * @param int $businessId Business ID
     * @return bool True if saved, false if unsaved
     */
    public static function toggle($userId, $businessId): bool
    {
        if (!$businessId) {
            throw new \InvalidArgumentException('Must provide businessId');
        }

        $saved = static::where('user_id', $userId)
            ->where('business_id', $businessId)
            ->first();

        if ($saved) {
            $saved->delete();
            
            // Update aggregate stats
            Business::find($businessId)?->updateAggregateStats();
            
            return false; // Unsaved
        }

        static::create([
            'user_id' => $userId,
            'business_id' => $businessId,
        ]);

        // Update aggregate stats
        Business::find($businessId)?->updateAggregateStats();

        return true; // Saved
    }

    /**
     * Check if user has saved a business
     * 
     * @param int $userId User ID
     * @param int $businessId Business ID
     * @return bool
     */
    public static function isSaved($userId, $businessId): bool
    {
        if (!$businessId) {
            throw new \InvalidArgumentException('Must provide businessId');
        }

        return static::where('user_id', $userId)
            ->where('business_id', $businessId)
            ->exists();
    }

    /**
     * Get all saved businesses for a user
     * 
     * @param int $userId User ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getSavedBusinesses($userId)
    {
        return static::where('user_id', $userId)
            ->with('business')
            ->get();
    }

    /**
     * Get total count of saved items for a user
     */
    public static function getTotalCount($userId): int
    {
        return static::where('user_id', $userId)->count();
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope for saved standalone businesses only
     */
    public function scopeBusinesses($query)
    {
        return $query->whereNotNull('business_id')->whereNull('business_branch_id');
    }

    /**
     * Scope for saved branches only
     */
    public function scopeBranches($query)
    {
        return $query->whereNotNull('business_branch_id')->whereNull('business_id');
    }

    /**
     * Scope for a specific user's saved items
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for recent saves
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ============================================
    // VALIDATION
    // ============================================

    /**
     * Boot method to ensure saved item belongs to a business
     */
    protected static function booted()
    {
        static::creating(function ($saved) {
            // Ensure belongs to a business
            if (!$saved->business_id) {
                throw new \Exception('SavedBusiness must belong to a business.');
            }
        });
    }
}