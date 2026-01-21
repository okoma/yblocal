<?php
// app/Models/BusinessView.php - COMPLETE FIXED VERSION

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessView extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',           // Business relationship
        'referral_source',
        'country',
        'country_code',
        'region',
        'city',
        'ip_address',
        'user_agent',
        'device_type',
        'viewed_at',
        'view_date',
        'view_hour',
        'view_month',
        'view_year',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'view_date' => 'date',
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
     * Get the parent business
     */
    public function parent()
    {
        return $this->business;
    }

    /**
     * Check if view is for business
     */
    public function isForBusiness(): bool
    {
        return !is_null($this->business_id);
    }

    /**
     * Record a view for a business
     * 
     * @param int $businessId Business ID
     * @param string $referralSource Source of traffic (e.g., 'yellowbooks', 'google', 'direct')
     * @return static
     */
    public static function recordView($businessId, $referralSource = 'direct')
    {
        if (!$businessId) {
            throw new \InvalidArgumentException('Must provide businessId');
        }

        $now = now();

        return static::create([
            'business_id' => $businessId,
            'referral_source' => $referralSource,
            'country' => 'Unknown', // TODO: Integrate with IP geolocation service
            'country_code' => null,
            'region' => 'Unknown',
            'city' => 'Unknown',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'device_type' => static::detectDevice(),
            'viewed_at' => $now,
            'view_date' => $now->toDateString(),
            'view_hour' => $now->format('H'),
            'view_month' => $now->format('Y-m'),
            'view_year' => $now->format('Y'),
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
     * Scope for views by referral source
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('referral_source', $source);
    }

    /**
     * Scope for YellowBooks-originated views only
     */
    public function scopeYellowbooksOnly($query)
    {
        return $query->where('referral_source', 'yellowbooks');
    }

    /**
     * Scope for today's views
     */
    public function scopeToday($query)
    {
        return $query->whereDate('view_date', today());
    }

    /**
     * Scope for this month's views
     */
    public function scopeThisMonth($query)
    {
        return $query->where('view_month', now()->format('Y-m'));
    }

    /**
     * Scope for this year's views
     */
    public function scopeThisYear($query)
    {
        return $query->where('view_year', now()->format('Y'));
    }

    /**
     * Scope for views of a specific business
     */
    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for views by device type
     */
    public function scopeByDevice($query, string $device)
    {
        return $query->where('device_type', $device);
    }

    /**
     * Scope for mobile views
     */
    public function scopeMobile($query)
    {
        return $query->where('device_type', 'mobile');
    }

    /**
     * Scope for desktop views
     */
    public function scopeDesktop($query)
    {
        return $query->where('device_type', 'desktop');
    }

    /**
     * Scope for tablet views
     */
    public function scopeTablet($query)
    {
        return $query->where('device_type', 'tablet');
    }

    /**
     * Scope for views in date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('view_date', [$startDate, $endDate]);
    }

    /**
     * Scope for views by country
     */
    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope for views by city
     */
    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    // ============================================
    // VALIDATION
    // ============================================

    /**
     * Boot method to ensure view belongs to a business
     */
    protected static function booted()
    {
        static::creating(function ($view) {
            // Ensure view belongs to a business
            if (!$view->business_id) {
                throw new \Exception('View must belong to a business.');
            }
        });
    }
}