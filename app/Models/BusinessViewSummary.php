<?php
// ============================================
// app/Models/BusinessViewSummary.php
// Aggregated analytics summaries for businesses
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\ReferralSource;
use App\Enums\PageType;
use App\Enums\DeviceType;

class BusinessViewSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'period_type',           // 'hourly', 'daily', 'monthly', 'yearly'
        'period_key',            // e.g., '2024-01-15-14', '2024-01-15', '2024-01', '2024'
        'total_views',
        'views_by_source',       // JSON: {'yellowbooks': 100, 'google': 50, ...}
        'views_by_country',      // JSON: {'NG': 200, 'US': 50, ...}
        'views_by_city',         // JSON: {'Lagos': 100, 'Abuja': 50, ...}
        'views_by_device',       // JSON: {'mobile': 150, 'desktop': 100, 'tablet': 50}
        'total_impressions',     // Total impressions (listings visible)
        'impressions_by_source', // JSON: {'yellowbooks': 500, 'google': 200, ...}
        'impressions_by_page_type', // JSON: {'archive': 400, 'category': 200, 'search': 100}
        'impressions_by_country', // JSON: {'NG': 500, 'US': 100, ...}
        'impressions_by_city',    // JSON: {'Lagos': 300, 'Abuja': 200, ...}
        'impressions_by_device',  // JSON: {'mobile': 400, 'desktop': 300, 'tablet': 100}
        'total_clicks',          // Total clicks (cookie-based, one per person)
        'clicks_by_source',      // JSON: {'yellowbooks': 50, 'google': 30, ...}
        'clicks_by_page_type',   // JSON: {'archive': 40, 'category': 20, 'search': 20}
        'clicks_by_country',     // JSON: {'NG': 60, 'US': 20, ...}
        'clicks_by_city',        // JSON: {'Lagos': 40, 'Abuja': 20, ...}
        'clicks_by_device',      // JSON: {'mobile': 50, 'desktop': 30, 'tablet': 10}
        'unique_visitors',       // Count of unique cookie_ids
        'total_calls',
        'total_whatsapp',
        'total_emails',
        'total_website_clicks',
        'total_map_clicks',
        'interactions_by_source', // JSON: {'yellowbooks': 80, 'google': 40, ...}
        'interactions_by_device', // JSON: {'mobile': 70, 'desktop': 50, ...}
        'total_leads',           // Total leads generated
        'leads_by_status',       // JSON: {'new': 10, 'contacted': 5, 'converted': 2, ...}
    ];

    protected $casts = [
        'views_by_source' => 'array',
        'views_by_country' => 'array',
        'views_by_city' => 'array',
        'views_by_device' => 'array',
        'impressions_by_source' => 'array',
        'impressions_by_page_type' => 'array',
        'impressions_by_country' => 'array',
        'impressions_by_city' => 'array',
        'impressions_by_device' => 'array',
        'clicks_by_source' => 'array',
        'clicks_by_page_type' => 'array',
        'clicks_by_country' => 'array',
        'clicks_by_city' => 'array',
        'clicks_by_device' => 'array',
        'interactions_by_source' => 'array',
        'interactions_by_device' => 'array',
        'leads_by_status' => 'array',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    // ============================================
    // BOOT METHOD
    // ============================================

    protected static function booted()
    {
        static::creating(function ($summary) {
            if (!$summary->business_id) {
                throw new \Exception('BusinessViewSummary must belong to a business.');
            }
        });
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Aggregate statistics for a business
     * 
     * @param int $businessId Business ID
     * @param string $periodType 'hourly', 'daily', 'monthly', 'yearly'
     * @param string $periodKey e.g., '2024-01-15-14', '2024-01-15', '2024-01', '2024'
     * @return static
     */
    public static function aggregateFor(int $businessId, string $periodType, string $periodKey): static
    {
        // Build views query
        $viewsQuery = BusinessView::where('business_id', $businessId);
        $viewsQuery = static::applyPeriodFilter($viewsQuery, $periodType, $periodKey);
        $views = $viewsQuery->get();

        // Build impressions query
        $impressionsQuery = BusinessImpression::where('business_id', $businessId);
        $impressionsQuery = static::applyPeriodFilter(
            $impressionsQuery, 
            $periodType, 
            $periodKey, 
            'impression_date', 
            'impression_hour', 
            'impression_month', 
            'impression_year'
        );
        $impressions = $impressionsQuery->get();

        // Build clicks query
        $clicksQuery = BusinessClick::where('business_id', $businessId);
        $clicksQuery = static::applyPeriodFilter(
            $clicksQuery, 
            $periodType, 
            $periodKey, 
            'click_date', 
            'click_hour', 
            'click_month', 
            'click_year'
        );
        $clicks = $clicksQuery->get();

        // Build interactions query (if you have this model)
        $interactions = collect(); // Empty collection if no interactions model yet
        if (class_exists('App\Models\BusinessInteraction')) {
            $interactionsQuery = \App\Models\BusinessInteraction::where('business_id', $businessId);
            $interactionsQuery = static::applyPeriodFilter(
                $interactionsQuery, 
                $periodType, 
                $periodKey, 
                'interaction_date', 
                'interaction_hour', 
                'interaction_month', 
                'interaction_year'
            );
            $interactions = $interactionsQuery->get();
        }

        // Build leads query
        $leads = collect();
        if (class_exists('App\Models\Lead')) {
            $leadsQuery = \App\Models\Lead::where('business_id', $businessId);
            $leadsQuery = static::applyPeriodFilter(
                $leadsQuery,
                $periodType,
                $periodKey,
                'created_at',
                null,
                null,
                null
            );
            $leads = $leadsQuery->get();
        }

        return static::updateOrCreate(
            [
                'business_id' => $businessId,
                'period_type' => $periodType,
                'period_key' => $periodKey,
            ],
            [
                'total_views' => $views->count(),
                'views_by_source' => static::groupByEnum($views, 'referral_source'),
                'views_by_country' => $views->groupBy('country')->map->count()->toArray(),
                'views_by_city' => $views->groupBy('city')->map->count()->toArray(),
                'views_by_device' => static::groupByEnum($views, 'device_type'),
                'total_impressions' => $impressions->count(),
                'impressions_by_source' => static::groupByEnum($impressions, 'referral_source'),
                'impressions_by_page_type' => static::groupByEnum($impressions, 'page_type'),
                'impressions_by_country' => $impressions->groupBy('country')->map->count()->toArray(),
                'impressions_by_city' => $impressions->groupBy('city')->map->count()->toArray(),
                'impressions_by_device' => static::groupByEnum($impressions, 'device_type'),
                'total_clicks' => $clicks->count(),
                'clicks_by_source' => static::groupByEnum($clicks, 'referral_source'),
                'clicks_by_page_type' => static::groupByEnum($clicks, 'source_page_type'),
                'clicks_by_country' => $clicks->groupBy('country')->map->count()->toArray(),
                'clicks_by_city' => $clicks->groupBy('city')->map->count()->toArray(),
                'clicks_by_device' => static::groupByEnum($clicks, 'device_type'),
                'unique_visitors' => $clicks->pluck('cookie_id')->unique()->count(),
                'total_calls' => $interactions->where('interaction_type', 'call')->count(),
                'total_whatsapp' => $interactions->where('interaction_type', 'whatsapp')->count(),
                'total_emails' => $interactions->where('interaction_type', 'email')->count(),
                'total_website_clicks' => $interactions->where('interaction_type', 'website')->count(),
                'total_map_clicks' => $interactions->whereIn('interaction_type', ['map', 'directions'])->count(),
                'interactions_by_source' => static::groupByEnum($interactions, 'referral_source'),
                'interactions_by_device' => static::groupByEnum($interactions, 'device_type'),
                'total_leads' => $leads->count(),
                'leads_by_status' => $leads->groupBy('status')->map->count()->toArray(),
            ]
        );
    }

    /**
     * Helper to group by enum values
     */
    private static function groupByEnum($collection, string $field): array
    {
        return $collection->groupBy(function($item) use ($field) {
            $value = $item->$field;
            // If it's an enum, get its value, otherwise use as-is
            return $value instanceof \BackedEnum ? $value->value : $value;
        })->map->count()->toArray();
    }

    /**
     * Apply period filter to query
     */
    private static function applyPeriodFilter(
        $query, 
        string $periodType, 
        string $periodKey, 
        string $dateField = 'view_date', 
        ?string $hourField = 'view_hour', 
        ?string $monthField = 'view_month', 
        ?string $yearField = 'view_year'
    ) {
        // For created_at timestamps (leads), extract date parts directly
        if ($dateField === 'created_at') {
            return match($periodType) {
                'hourly' => $query->whereRaw("DATE_FORMAT({$dateField}, '%Y-%m-%d-%H') = ?", [$periodKey]),
                'daily' => $query->whereDate($dateField, $periodKey),
                'monthly' => $query->whereRaw("DATE_FORMAT({$dateField}, '%Y-%m') = ?", [$periodKey]),
                'yearly' => $query->whereYear($dateField, $periodKey),
                default => throw new \InvalidArgumentException("Invalid period type: {$periodType}")
            };
        }

        return match($periodType) {
            'hourly' => $query->where($dateField, substr($periodKey, 0, 10))
                             ->where($hourField, substr($periodKey, -2)),
            'daily' => $query->where($dateField, $periodKey),
            'monthly' => $query->where($monthField, $periodKey),
            'yearly' => $query->where($yearField, $periodKey),
            default => throw new \InvalidArgumentException("Invalid period type: {$periodType}")
        };
    }

    /**
     * Generate period key for current time
     */
    public static function generatePeriodKey(string $periodType, ?\Carbon\Carbon $time = null): string
    {
        $time = $time ?? now();
        
        return match($periodType) {
            'hourly' => $time->format('Y-m-d-H'),
            'daily' => $time->format('Y-m-d'),
            'monthly' => $time->format('Y-m'),
            'yearly' => $time->format('Y'),
            default => throw new \InvalidArgumentException("Invalid period type: {$periodType}")
        };
    }

    // ============================================
    // CALCULATED METRICS
    // ============================================

    public function getTotalInteractions(): int
    {
        return $this->total_calls 
            + $this->total_whatsapp 
            + $this->total_emails 
            + $this->total_website_clicks 
            + $this->total_map_clicks;
    }

    public function getConversionRate(): float
    {
        if ($this->total_views === 0) {
            return 0;
        }
        
        return round(($this->getTotalInteractions() / $this->total_views) * 100, 2);
    }

    public function getClickThroughRate(): float
    {
        if ($this->total_impressions === 0) {
            return 0;
        }
        
        return round(($this->total_clicks / $this->total_impressions) * 100, 2);
    }

    public function getTopReferralSource(): ?string
    {
        if (empty($this->views_by_source)) {
            return null;
        }
        
        arsort($this->views_by_source);
        return array_key_first($this->views_by_source);
    }

    public function getTopDevice(): ?string
    {
        if (empty($this->views_by_device)) {
            return null;
        }
        
        $sorted = $this->views_by_device;
        arsort($sorted);
        return array_key_first($sorted);
    }

    public function getTopPageType(): ?string
    {
        if (empty($this->impressions_by_page_type)) {
            return null;
        }
        
        $sorted = $this->impressions_by_page_type;
        arsort($sorted);
        return array_key_first($sorted);
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeByPeriodType($query, string $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    public function scopeHourly($query)
    {
        return $query->where('period_type', 'hourly');
    }

    public function scopeDaily($query)
    {
        return $query->where('period_type', 'daily');
    }

    public function scopeMonthly($query)
    {
        return $query->where('period_type', 'monthly');
    }

    public function scopeYearly($query)
    {
        return $query->where('period_type', 'yearly');
    }

    public function scopeForPeriod($query, string $periodKey)
    {
        return $query->where('period_key', $periodKey);
    }

    public function scopePeriodRange($query, string $startKey, string $endKey)
    {
        return $query->whereBetween('period_key', [$startKey, $endKey]);
    }
}