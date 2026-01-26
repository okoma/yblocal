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

class BusinessClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'business_branch_id',
        'cookie_id',            // Unique cookie identifier to prevent duplicates
        'referral_source',      // 'yellowbooks', 'google', 'direct', etc.
        'source_page_type',     // 'archive', 'category', 'search', 'external', etc.
        'country',
        'country_code',
        'region',
        'city',
        'ip_address',
        'user_agent',
        'device_type',
        'clicked_at',
        'click_date',
        'click_hour',
        'click_month',
        'click_year',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
        'click_date' => 'date',
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
     * Get or create a unique cookie ID for the visitor
     * This ensures one click per person per business
     */
    public static function getCookieId($businessId): string
    {
        $cookieName = "yb_click_{$businessId}";
        
        // Try to get existing cookie from request
        $cookieId = request()->cookie($cookieName);
        
        if (!$cookieId) {
            // Generate unique ID
            $cookieId = Str::random(32) . '_' . time();
            
            // Queue cookie to be set in response (30 days)
            Cookie::queue($cookieName, $cookieId, 60 * 24 * 30);
        }
        
        return $cookieId;
    }

    /**
     * Record a click when someone visits the business detail page
     * Only records if this person hasn't clicked before (cookie-based)
     * 
     * @param int $businessId Business ID
     * @param string $referralSource Source of traffic (e.g., 'yellowbooks', 'google', 'direct')
     * @param string|null $sourcePageType Where the click came from ('archive', 'category', 'external', etc.)
     * @return static|null Returns null if click already recorded (duplicate)
     */
    public static function recordClick($businessId, $referralSource = 'direct', $sourcePageType = null)
    {
        if (!$businessId) {
            throw new \InvalidArgumentException('Must provide businessId');
        }

        // Get or create cookie ID
        $cookieId = static::getCookieId($businessId);
        
        // Check if this person already clicked this business
        $existingClick = static::where('business_id', $businessId)
            ->where('cookie_id', $cookieId)
            ->first();
        
        // If already clicked, don't record again (cookie-based deduplication)
        if ($existingClick) {
            return null; // Click already recorded for this person
        }

        $now = now();

        return static::create([
            'business_id' => $businessId,
            'cookie_id' => $cookieId,
            'referral_source' => $referralSource,
            'source_page_type' => $sourcePageType,
            'country' => 'Unknown', // TODO: Integrate with IP geolocation service
            'country_code' => null,
            'region' => 'Unknown',
            'city' => 'Unknown',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'device_type' => static::detectDevice(),
            'clicked_at' => $now,
            'click_date' => $now->toDateString(),
            'click_hour' => $now->format('H'),
            'click_month' => $now->format('Y-m'),
            'click_year' => $now->format('Y'),
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

    /**
     * Detect referral source from HTTP referer header
     */
    public static function detectReferralSource($referer = null): string
    {
        if (!$referer) {
            $referer = request()->header('referer');
        }
        
        if (!$referer) {
            return 'direct';
        }
        
        // Check if from YellowBooks
        $appUrl = config('app.url');
        if (str_contains($referer, $appUrl)) {
            // Determine page type from URL
            if (str_contains($referer, '/category/')) {
                return 'yellowbooks'; // Will be categorized by source_page_type
            }
            if (str_contains($referer, '/search')) {
                return 'yellowbooks';
            }
            if (str_contains($referer, '/businesses') || str_contains($referer, '/archive')) {
                return 'yellowbooks';
            }
            return 'yellowbooks';
        }
        
        // External sources
        if (str_contains($referer, 'google.com')) return 'google';
        if (str_contains($referer, 'bing.com')) return 'bing';
        if (str_contains($referer, 'facebook.com')) return 'facebook';
        if (str_contains($referer, 'instagram.com')) return 'instagram';
        if (str_contains($referer, 'twitter.com') || str_contains($referer, 'x.com')) return 'twitter';
        if (str_contains($referer, 'linkedin.com')) return 'linkedin';
        
        return 'other';
    }

    /**
     * Detect source page type from HTTP referer
     */
    public static function detectSourcePageType($referer = null): ?string
    {
        if (!$referer) {
            $referer = request()->header('referer');
        }
        
        if (!$referer) {
            return 'external'; // Direct visit
        }
        
        $appUrl = config('app.url');
        if (!str_contains($referer, $appUrl)) {
            return 'external'; // External source
        }
        
        // Determine from URL path
        if (str_contains($referer, '/category/')) return 'category';
        if (str_contains($referer, '/search')) return 'search';
        if (str_contains($referer, '/businesses') || str_contains($referer, '/archive')) return 'archive';
        if (str_contains($referer, '/related')) return 'related';
        if (str_contains($referer, '/featured')) return 'featured';
        
        return 'other';
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope for clicks by referral source
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('referral_source', $source);
    }

    /**
     * Scope for clicks by source page type
     */
    public function scopeBySourcePageType($query, string $pageType)
    {
        return $query->where('source_page_type', $pageType);
    }

    /**
     * Scope for clicks of a specific business
     */
    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for today's clicks
     */
    public function scopeToday($query)
    {
        return $query->whereDate('click_date', today());
    }

    /**
     * Scope for this month's clicks
     */
    public function scopeThisMonth($query)
    {
        return $query->where('click_month', now()->format('Y-m'));
    }

    /**
     * Scope for clicks in date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('click_date', [$startDate, $endDate]);
    }

    // ============================================
    // VALIDATION
    // ============================================

    /**
     * Boot method to ensure click belongs to a business
     */
    protected static function booted()
    {
        static::creating(function ($click) {
            if (!$click->business_id) {
                throw new \Exception('Click must belong to a business.');
            }
        });
    }
}
