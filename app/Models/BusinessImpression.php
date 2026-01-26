<?php
// ============================================
// app/Models/BusinessImpression.php
// Track impressions when business listings are visible
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessImpression extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'business_branch_id',
        'page_type',           // 'archive', 'category', 'search', etc.
        'referral_source',     // 'yellowbooks', 'google', 'direct', etc.
        'country',
        'country_code',
        'region',
        'city',
        'ip_address',
        'user_agent',
        'device_type',
        'impressed_at',
        'impression_date',
        'impression_hour',
        'impression_month',
        'impression_year',
    ];

    protected $casts = [
        'impressed_at' => 'datetime',
        'impression_date' => 'date',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    /**
     * Business (for standalone businesses)
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Record an impression when a business listing is visible
     * 
     * @param int $businessId Business ID
     * @param string $pageType Where the listing is visible ('archive', 'category', 'search', etc.)
     * @param string $referralSource Source of traffic (e.g., 'yellowbooks', 'google', 'direct')
     * @return static
     */
    public static function recordImpression($businessId, $pageType = 'archive', $referralSource = 'direct')
    {
        if (!$businessId) {
            throw new \InvalidArgumentException('Must provide businessId');
        }

        // Validate page type
        $validPageTypes = ['archive', 'category', 'search', 'related', 'featured', 'other'];
        if (!in_array($pageType, $validPageTypes)) {
            throw new \InvalidArgumentException("Invalid page type: {$pageType}");
        }

        $now = now();

        return static::create([
            'business_id' => $businessId,
            'page_type' => $pageType,
            'referral_source' => $referralSource,
            'country' => 'Unknown', // TODO: Integrate with IP geolocation service
            'country_code' => null,
            'region' => 'Unknown',
            'city' => 'Unknown',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'device_type' => static::detectDevice(),
            'impressed_at' => $now,
            'impression_date' => $now->toDateString(),
            'impression_hour' => $now->format('H'),
            'impression_month' => $now->format('Y-m'),
            'impression_year' => $now->format('Y'),
        ]);
    }

    /**
     * Detect device type from user agent
     */
    private static function detectDevice(): string
    {
        $userAgent = request()->userAgent();
        
        if (preg_match('/mobile|android|iphone/i', $userAgent)) {
            return 'mobile';
        }
        
        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        }
        
        return 'desktop';
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope for impressions by page type
     */
    public function scopeByPageType($query, string $pageType)
    {
        return $query->where('page_type', $pageType);
    }

    /**
     * Scope for impressions by referral source
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('referral_source', $source);
    }

    /**
     * Scope for impressions of a specific business
     */
    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for today's impressions
     */
    public function scopeToday($query)
    {
        return $query->whereDate('impression_date', today());
    }

    /**
     * Scope for this month's impressions
     */
    public function scopeThisMonth($query)
    {
        return $query->where('impression_month', now()->format('Y-m'));
    }

    /**
     * Scope for impressions in date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('impression_date', [$startDate, $endDate]);
    }

    // ============================================
    // VALIDATION
    // ============================================

    /**
     * Boot method to ensure impression belongs to a business
     */
    protected static function booted()
    {
        static::creating(function ($impression) {
            if (!$impression->business_id) {
                throw new \Exception('Impression must belong to a business.');
            }
        });
    }
}
