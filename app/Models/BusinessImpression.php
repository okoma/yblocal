<?php
// ============================================
// app/Models/BusinessImpression.php
// Track impressions when business listings are visible
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Enums\ReferralSource;
use App\Enums\PageType;
use App\Enums\DeviceType;
use App\Services\GeolocationService;

class BusinessImpression extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'page_type',           // PageType enum
        'referral_source',     // ReferralSource enum
        'country',
        'country_code',
        'region',
        'city',
        'ip_address',
        'user_agent',
        'device_type',         // DeviceType enum
        'impressed_at',
        'impression_date',
        'impression_hour',
        'impression_month',
        'impression_year',
    ];

    protected $casts = [
        'impressed_at' => 'datetime',
        'impression_date' => 'date',
        'page_type' => PageType::class,
        'referral_source' => ReferralSource::class,
        'device_type' => DeviceType::class,
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    // ============================================
    // BOOT METHOD - Auto-fill date/time fields
    // ============================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($impression) {
            if (!$impression->business_id) {
                throw new \Exception('Impression must belong to a business.');
            }

            $now = $impression->impressed_at ? Carbon::parse($impression->impressed_at) : now();
            
            $impression->impressed_at = $now;
            $impression->impression_date = $now->toDateString();
            $impression->impression_hour = $now->format('H');
            $impression->impression_month = $now->format('Y-m');
            $impression->impression_year = $now->format('Y');
        });
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Record an impression when a business listing is visible
     * 
     * @param int $businessId Business ID
     * @param PageType|string $pageType Where the listing is visible
     * @param ReferralSource|string|null $referralSource Source of traffic
     * @return static
     */
    public static function recordImpression(
        int $businessId,
        PageType|string $pageType = PageType::ARCHIVE,
        ReferralSource|string|null $referralSource = null
    ): static {
        // Convert strings to enums if needed
        if (is_string($pageType)) {
            $pageType = PageType::from($pageType);
        }
        if (is_string($referralSource)) {
            $referralSource = ReferralSource::tryFrom($referralSource) ?? ReferralSource::DIRECT;
        }

        $referralSource = $referralSource ?? ReferralSource::DIRECT;

        // Get geolocation data
        $location = GeolocationService::getLocationData(request()->ip());

        return static::create([
            'business_id' => $businessId,
            'page_type' => $pageType,
            'referral_source' => $referralSource,
            'country' => $location['country'],
            'country_code' => $location['country_code'],
            'region' => $location['region'],
            'city' => $location['city'],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'device_type' => DeviceType::detect(request()->userAgent()),
        ]);
    }

    /**
     * Record bulk impressions (e.g., all businesses on a page)
     */
    public static function recordBulkImpressions(
        array $businessIds,
        PageType|string $pageType,
        ReferralSource|string|null $referralSource = null
    ): int {
        if (is_string($pageType)) {
            $pageType = PageType::from($pageType);
        }
        if (is_string($referralSource)) {
            $referralSource = ReferralSource::tryFrom($referralSource) ?? ReferralSource::DIRECT;
        }

        $referralSource = $referralSource ?? ReferralSource::DIRECT;
        $now = now();
        
        // Get geolocation data once for all impressions
        $location = GeolocationService::getLocationData(request()->ip());
        
        $impressions = [];
        
        foreach ($businessIds as $businessId) {
            $impressions[] = [
                'business_id' => $businessId,
                'page_type' => $pageType->value,
                'referral_source' => $referralSource->value,
                'country' => $location['country'],
                'country_code' => $location['country_code'],
                'region' => $location['region'],
                'city' => $location['city'],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'device_type' => DeviceType::detect(request()->userAgent())->value,
                'impressed_at' => $now,
                'impression_date' => $now->toDateString(),
                'impression_hour' => $now->format('H'),
                'impression_month' => $now->format('Y-m'),
                'impression_year' => $now->format('Y'),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        return static::insert($impressions);
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeByPageType($query, PageType|string $pageType)
    {
        if (is_string($pageType)) {
            $pageType = PageType::from($pageType);
        }
        return $query->where('page_type', $pageType);
    }

    public function scopeBySource($query, ReferralSource|string $source)
    {
        if (is_string($source)) {
            $source = ReferralSource::from($source);
        }
        return $query->where('referral_source', $source);
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('impression_date', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->where('impression_month', now()->format('Y-m'));
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('impression_date', [$startDate, $endDate]);
    }

    public function scopeByDevice($query, DeviceType|string $device)
    {
        if (is_string($device)) {
            $device = DeviceType::from($device);
        }
        return $query->where('device_type', $device);
    }
}