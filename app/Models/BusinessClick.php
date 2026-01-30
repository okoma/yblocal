<?php
// ============================================
// app/Models/BusinessClick.php
// Track clicks to business detail pages (cookie-based, one per person)
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Enums\ReferralSource;
use App\Enums\PageType;
use App\Enums\DeviceType;
use App\Services\GeolocationService;

class BusinessClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'cookie_id',            // Unique cookie identifier to prevent duplicates
        'referral_source',      // ReferralSource enum
        'source_page_type',     // PageType enum
        'country',
        'country_code',
        'region',
        'city',
        'ip_address',
        'user_agent',
        'device_type',          // DeviceType enum
        'clicked_at',
        'click_date',
        'click_hour',
        'click_month',
        'click_year',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
        'click_date' => 'date',
        'referral_source' => ReferralSource::class,
        'source_page_type' => PageType::class,
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

        static::creating(function ($click) {
            if (!$click->business_id) {
                throw new \Exception('Click must belong to a business.');
            }

            $now = $click->clicked_at ? Carbon::parse($click->clicked_at) : now();
            
            $click->clicked_at = $now;
            $click->click_date = $now->toDateString();
            $click->click_hour = $now->format('H');
            $click->click_month = $now->format('Y-m');
            $click->click_year = $now->format('Y');
        });
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Get or create a unique cookie ID for the visitor
     */
    public static function getCookieId(int $businessId): string
    {
        $cookieName = "yb_click_{$businessId}";
        
        $cookieId = request()->cookie($cookieName);
        
        if (!$cookieId) {
            $cookieId = Str::random(32) . '_' . time();
            Cookie::queue($cookieName, $cookieId, 60 * 24 * 30);
        }
        
        return $cookieId;
    }

    /**
     * Record a click when someone visits the business detail page
     * 
     * @param int $businessId Business ID
     * @param ReferralSource|string|null $referralSource Source of traffic
     * @param PageType|string|null $sourcePageType Where the click came from
     * @return static|null Returns null if click already recorded
     */
    public static function recordClick(
        int $businessId, 
        ReferralSource|string|null $referralSource = null, 
        PageType|string|null $sourcePageType = null
    ): ?static {
        // Get or create cookie ID
        $cookieId = static::getCookieId($businessId);
        
        // Check if already clicked (deduplication)
        if (static::where('business_id', $businessId)->where('cookie_id', $cookieId)->exists()) {
            return null;
        }

        // Convert strings to enums if needed
        if (is_string($referralSource)) {
            $referralSource = ReferralSource::tryFrom($referralSource) ?? ReferralSource::DIRECT;
        }
        if (is_string($sourcePageType)) {
            $sourcePageType = PageType::tryFrom($sourcePageType);
        }

        // Auto-detect if not provided
        $referralSource = $referralSource ?? static::detectReferralSource();
        $sourcePageType = $sourcePageType ?? static::detectSourcePageType();

        // Get geolocation data
        $location = GeolocationService::getLocationData(request()->ip());

        return static::create([
            'business_id' => $businessId,
            'cookie_id' => $cookieId,
            'referral_source' => $referralSource,
            'source_page_type' => $sourcePageType,
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
     * Detect referral source from HTTP referer
     */
    public static function detectReferralSource(?string $referer = null): ReferralSource
    {
        $referer = $referer ?? request()->header('referer');
        
        if (!$referer) {
            return ReferralSource::DIRECT;
        }
        
        $appUrl = parse_url(config('app.url'), PHP_URL_HOST);
        if (str_contains($referer, $appUrl)) {
            return ReferralSource::YELLOWBOOKS;
        }
        
        if (str_contains($referer, 'google.com')) return ReferralSource::GOOGLE;
        if (str_contains($referer, 'bing.com')) return ReferralSource::BING;
        if (str_contains($referer, 'facebook.com')) return ReferralSource::FACEBOOK;
        if (str_contains($referer, 'instagram.com')) return ReferralSource::INSTAGRAM;
        if (str_contains($referer, 'twitter.com') || str_contains($referer, 'x.com')) return ReferralSource::TWITTER;
        if (str_contains($referer, 'linkedin.com')) return ReferralSource::LINKEDIN;
        
        return ReferralSource::OTHER;
    }

    /**
     * Detect source page type from HTTP referer
     */
    public static function detectSourcePageType(?string $referer = null): ?PageType
    {
        $referer = $referer ?? request()->header('referer');
        
        if (!$referer) {
            return null;
        }
        
        $appUrl = parse_url(config('app.url'), PHP_URL_HOST);
        if (!str_contains($referer, $appUrl)) {
            return null; // External source
        }
        
        if (str_contains($referer, '/category/')) return PageType::CATEGORY;
        if (str_contains($referer, '/search')) return PageType::SEARCH;
        if (str_contains($referer, '/businesses') || str_contains($referer, '/archive')) return PageType::ARCHIVE;
        if (str_contains($referer, '/related')) return PageType::RELATED;
        if (str_contains($referer, '/featured')) return PageType::FEATURED;
        if ($referer === config('app.url') || $referer === config('app.url') . '/') return PageType::HOME;
        
        return PageType::OTHER;
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeBySource($query, ReferralSource|string $source)
    {
        if (is_string($source)) {
            $source = ReferralSource::from($source);
        }
        return $query->where('referral_source', $source);
    }

    public function scopeBySourcePageType($query, PageType|string $pageType)
    {
        if (is_string($pageType)) {
            $pageType = PageType::from($pageType);
        }
        return $query->where('source_page_type', $pageType);
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('click_date', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->where('click_month', now()->format('Y-m'));
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('click_date', [$startDate, $endDate]);
    }

    public function scopeByDevice($query, DeviceType|string $device)
    {
        if (is_string($device)) {
            $device = DeviceType::from($device);
        }
        return $query->where('device_type', $device);
    }
}