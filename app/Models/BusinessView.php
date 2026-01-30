<?php
// ============================================
// app/Models/BusinessView.php
// Track views on business detail pages
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Enums\ReferralSource;
use App\Enums\DeviceType;
use App\Services\GeolocationService;

class BusinessView extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'referral_source',     // ReferralSource enum
        'country',
        'country_code',
        'region',
        'city',
        'ip_address',
        'user_agent',
        'device_type',         // DeviceType enum
        'viewed_at',
        'view_date',
        'view_hour',
        'view_month',
        'view_year',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'view_date' => 'date',
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

        static::creating(function ($view) {
            if (!$view->business_id) {
                throw new \Exception('View must belong to a business.');
            }

            $now = $view->viewed_at ? Carbon::parse($view->viewed_at) : now();
            
            $view->viewed_at = $now;
            $view->view_date = $now->toDateString();
            $view->view_hour = $now->format('H');
            $view->view_month = $now->format('Y-m');
            $view->view_year = $now->format('Y');
        });
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Record a view for a business
     * 
     * @param int $businessId Business ID
     * @param ReferralSource|string|null $referralSource Source of traffic
     * @return static
     */
    public static function recordView(
        int $businessId,
        ReferralSource|string|null $referralSource = null
    ): static {
        // Convert string to enum if needed
        if (is_string($referralSource)) {
            $referralSource = ReferralSource::tryFrom($referralSource) ?? ReferralSource::DIRECT;
        }

        $referralSource = $referralSource ?? ReferralSource::DIRECT;

        // Get geolocation data
        $location = GeolocationService::getLocationData(request()->ip());

        return static::create([
            'business_id' => $businessId,
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

    public function scopeYellowbooksOnly($query)
    {
        return $query->where('referral_source', ReferralSource::YELLOWBOOKS);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('view_date', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->where('view_month', now()->format('Y-m'));
    }

    public function scopeThisYear($query)
    {
        return $query->where('view_year', now()->format('Y'));
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeByDevice($query, DeviceType|string $device)
    {
        if (is_string($device)) {
            $device = DeviceType::from($device);
        }
        return $query->where('device_type', $device);
    }

    public function scopeMobile($query)
    {
        return $query->where('device_type', DeviceType::MOBILE);
    }

    public function scopeDesktop($query)
    {
        return $query->where('device_type', DeviceType::DESKTOP);
    }

    public function scopeTablet($query)
    {
        return $query->where('device_type', DeviceType::TABLET);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('view_date', [$startDate, $endDate]);
    }

    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }
}