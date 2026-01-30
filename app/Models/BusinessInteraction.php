<?php
// app/Models/BusinessInteraction.php - UPDATED WITH ENUMS

namespace App\Models;

use App\Enums\DeviceType;
use App\Enums\ReferralSource;
use App\Services\GeolocationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',           // Business relationship
        'user_id',
        'interaction_type',      // 'call', 'whatsapp', 'email', 'website', 'map', 'directions'
        'referral_source',
        'country',
        'country_code',
        'region',
        'city',
        'ip_address',
        'user_agent',
        'device_type',
        'interacted_at',
        'interaction_date',
        'interaction_hour',
        'interaction_month',
        'interaction_year',
    ];

    protected $casts = [
        'interacted_at' => 'datetime',
        'interaction_date' => 'date',
        'referral_source' => ReferralSource::class,
        'device_type' => DeviceType::class,
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

    /**
     * User who performed the interaction (optional - can be guest)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * Check if interaction is for business
     */
    public function isForBusiness(): bool
    {
        return !is_null($this->business_id);
    }

    /**
     * Record an interaction (call, WhatsApp, email, etc.)
     * 
     * @param int $businessId Business ID
     * @param string $type Interaction type ('call', 'whatsapp', 'email', 'website', 'map', 'directions')
     * @param ReferralSource|string $referralSource Source of traffic
     * @param int|null $userId User ID if logged in
     * @return static
     */
    public static function recordInteraction(
        $businessId, 
        $type, 
        ReferralSource|string $referralSource = ReferralSource::DIRECT, 
        $userId = null
    ) {
        if (!$businessId) {
            throw new \InvalidArgumentException('Must provide businessId');
        }

        // Validate interaction type
        $validTypes = ['call', 'whatsapp', 'email', 'website', 'map', 'directions'];
        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException("Invalid interaction type: {$type}");
        }

        // Convert string to enum if needed
        if (is_string($referralSource)) {
            $referralSource = ReferralSource::tryFrom($referralSource) ?? ReferralSource::DIRECT;
        }

        $now = now();
        $deviceType = DeviceType::detect(request()->userAgent());

        // Get geolocation data
        $location = GeolocationService::getLocationData(request()->ip());

        return static::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'interaction_type' => $type,
            'referral_source' => $referralSource,
            'country' => $location['country'],
            'country_code' => $location['country_code'],
            'region' => $location['region'],
            'city' => $location['city'],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'device_type' => $deviceType,
            'interacted_at' => $now,
            'interaction_date' => $now->toDateString(),
            'interaction_hour' => $now->format('H'),
            'interaction_month' => $now->format('Y-m'),
            'interaction_year' => $now->format('Y'),
        ]);
    }

    /**
     * Detect device type from user agent
     * @deprecated Use DeviceType::detect() instead
     */
    private static function detectDevice(): string
    {
        return DeviceType::detect(request()->userAgent())->value;
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope for interactions by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('interaction_type', $type);
    }

    /**
     * Scope for call interactions
     */
    public function scopeCalls($query)
    {
        return $query->where('interaction_type', 'call');
    }

    /**
     * Scope for WhatsApp interactions
     */
    public function scopeWhatsApp($query)
    {
        return $query->where('interaction_type', 'whatsapp');
    }

    /**
     * Scope for email interactions
     */
    public function scopeEmails($query)
    {
        return $query->where('interaction_type', 'email');
    }

    /**
     * Scope for website clicks
     */
    public function scopeWebsiteClicks($query)
    {
        return $query->where('interaction_type', 'website');
    }

    /**
     * Scope for map/directions clicks
     */
    public function scopeMapClicks($query)
    {
        return $query->whereIn('interaction_type', ['map', 'directions']);
    }

    /**
     * Scope for today's interactions
     */
    public function scopeToday($query)
    {
        return $query->whereDate('interaction_date', today());
    }

    /**
     * Scope for this month's interactions
     */
    public function scopeThisMonth($query)
    {
        return $query->where('interaction_month', now()->format('Y-m'));
    }

    /**
     * Scope for interactions of a specific business
     */
    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for interactions by referral source
     */
    public function scopeBySource($query, ReferralSource|string $source)
    {
        $sourceValue = $source instanceof ReferralSource ? $source->value : $source;
        return $query->where('referral_source', $sourceValue);
    }

    /**
     * Scope for social media sources
     */
    public function scopeFromSocialMedia($query)
    {
        $socialSources = array_map(
            fn($case) => $case->value,
            array_filter(ReferralSource::cases(), fn($case) => $case->isSocialMedia())
        );
        return $query->whereIn('referral_source', $socialSources);
    }

    /**
     * Scope for search engine sources
     */
    public function scopeFromSearchEngines($query)
    {
        $searchSources = array_map(
            fn($case) => $case->value,
            array_filter(ReferralSource::cases(), fn($case) => $case->isSearchEngine())
        );
        return $query->whereIn('referral_source', $searchSources);
    }

    /**
     * Scope for interactions by device type
     */
    public function scopeByDevice($query, DeviceType|string $device)
    {
        $deviceValue = $device instanceof DeviceType ? $device->value : $device;
        return $query->where('device_type', $deviceValue);
    }

    /**
     * Scope for mobile interactions
     */
    public function scopeMobile($query)
    {
        return $query->where('device_type', DeviceType::MOBILE->value);
    }

    /**
     * Scope for desktop interactions
     */
    public function scopeDesktop($query)
    {
        return $query->where('device_type', DeviceType::DESKTOP->value);
    }

    /**
     * Scope for tablet interactions
     */
    public function scopeTablet($query)
    {
        return $query->where('device_type', DeviceType::TABLET->value);
    }

    /**
     * Scope for interactions in date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('interaction_date', [$startDate, $endDate]);
    }

    // ============================================
    // VALIDATION
    // ============================================

    /**
     * Boot method to ensure interaction belongs to a business
     */
    protected static function booted()
    {
        static::creating(function ($interaction) {
            // Ensure interaction belongs to a business
            if (!$interaction->business_id) {
                throw new \Exception('Interaction must belong to a business.');
            }
        });
    }
}