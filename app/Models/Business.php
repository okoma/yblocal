<?php
// app/Models/Business.php - COMPLETE FIXED VERSION WITH ENUM SUPPORT

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use App\Enums\ReferralSource;
use App\Enums\PageType;
use App\Enums\InteractionType;

class Business extends Model
{
    use HasFactory, SoftDeletes, HasSlug;

    protected $fillable = [
        'user_id',
        'business_type_id',
        'business_name',
        'slug',
        'description',
        'logo',
        'cover_photo',
        'gallery',
        
        // Contact Information
        'email',
        'phone',
        'whatsapp',
        'website',
        'whatsapp_message',
        
        // Location
        'state_location_id',
        'city_location_id',
        'state',
        'city',
        'area',
        'address',
        'latitude',
        'longitude',
        
        // Legal Information
        'registration_number',
        'entity_type',
        'years_in_business',
        
        // Business Hours
        'business_hours',
        
        // Verification & Status
        'is_claimed',
        'claimed_by',
        'is_verified',
        'verification_level',
        'verification_score',
        'is_premium',
        'premium_until',
        'status',
        
        // Aggregated Stats
        'avg_rating',
        'total_reviews',
        'total_views',
        'total_leads',
        'total_saves',
    ];

    protected $casts = [
        'gallery' => 'array',
        'business_hours' => 'array',
        'unique_features' => 'array',
        'is_claimed' => 'boolean',
        'is_verified' => 'boolean',
        'is_premium' => 'boolean',
        'premium_until' => 'datetime',
        'verification_score' => 'integer',
        'avg_rating' => 'float',
        'total_reviews' => 'integer',
        'total_views' => 'integer',
        'total_leads' => 'integer',
        'total_saves' => 'integer',
    ];

    // ============================================
    // SLUG CONFIGURATION
    // ============================================
    
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('business_name')
            ->saveSlugsTo('slug');
    }

    // ============================================
    // CORE RELATIONSHIPS
    // ============================================
    
    /**
     * Business Owner
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Business Subscriptions
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Active Subscription
     */
    public function subscription(): HasMany
    {
        return $this->subscriptions()->where('status', 'active')->latest();
    }

    /**
     * Get the active subscription
     */
    public function activeSubscription()
    {
        // Return active subscriptions OR cancelled subscriptions that haven't expired yet
        // (cancelled subscriptions remain valid until ends_at)
        return $this->subscriptions()
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->orWhere(function ($q) {
                        $q->where('status', 'cancelled')
                          ->where('ends_at', '>', now());
                    });
            })
            ->where('ends_at', '>', now())
            ->orderBy('ends_at', 'desc')
            ->first();
    }

    /**
     * Check if business has an active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    /**
     * Check if subscription is expired
     */
    public function isSubscriptionExpired(): bool
    {
        $subscription = $this->activeSubscription();
        return $subscription && $subscription->isExpired();
    }

    /**
     * Business Wallet
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Business Transactions (wallet transactions)
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Business Payment Transactions
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * User who claimed this business
     */
    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    /**
     * All claim requests for this business
     */
    public function claims(): HasMany
    {
        return $this->hasMany(BusinessClaim::class);
    }

    /**
     * All verification attempts for this business
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(BusinessVerification::class);
    }

    /**
     * Current active verification
     */
    public function currentVerification(): BelongsTo
    {
        return $this->belongsTo(BusinessVerification::class, 'current_verification_id');
    }

    /**
     * Business Type (e.g., Restaurant, Hotel, etc.)
     */
    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    /**
     * Business Categories
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'business_category');
    }

    /**
     * State Location
     */
    public function stateLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'state_location_id');
    }

    /**
     * City Location
     */
    public function cityLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'city_location_id');
    }

    /**
     * Location (alias for state location, for compatibility)
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'state_location_id');
    }

    // ============================================
    // DIRECT RELATIONSHIPS (For Standalone Businesses)
    // ============================================
    
    /**
     * Products/Services
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Customer Leads
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Quote Responses
     */
    public function quoteResponses(): HasMany
    {
        return $this->hasMany(QuoteResponse::class);
    }

    /**
     * Business Officials
     */
    public function officials(): HasMany
    {
        return $this->hasMany(Official::class);
    }

    /**
     * Social Media Accounts
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    // ============================================
    // VIEWS, INTERACTIONS & ANALYTICS
    // ============================================

    /**
     * Business Views
     */
    public function views(): HasMany
    {
        return $this->hasMany(BusinessView::class);
    }

    /**
     * Customer Interactions (calls, WhatsApp, email, website, map)
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(BusinessInteraction::class);
    }

    /**
     * Business Impressions (when listings are visible on archive/category/search pages)
     */
    public function impressions(): HasMany
    {
        return $this->hasMany(BusinessImpression::class);
    }

    /**
     * Business Clicks (cookie-based, one per person)
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(BusinessClick::class);
    }

    /**
     * View Summaries (aggregated analytics)
     */
    public function viewSummaries(): HasMany
    {
        return $this->hasMany(BusinessViewSummary::class);
    }

    /**
     * Users who saved/bookmarked this business
     */
    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_businesses', 'business_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Business Managers (users who manage this business)
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_managers', 'business_id', 'user_id')
            ->using(BusinessManager::class)
            ->withPivot(['position', 'permissions', 'is_active', 'is_primary', 'joined_at'])
            ->wherePivot('is_active', true)
            ->withTimestamps();
    }

    /**
     * All business manager assignments (including inactive)
     */
    public function managerAssignments(): HasMany
    {
        return $this->hasMany(BusinessManager::class);
    }

    /**
     * Active business managers only
     */
    public function activeManagers(): HasMany
    {
        return $this->hasMany(BusinessManager::class)->where('is_active', true);
    }

    /**
     * Manager invitations for this business
     */
    public function managerInvitations(): HasMany
    {
        return $this->hasMany(ManagerInvitation::class);
    }

    /**
     * Pending manager invitations
     */
    public function pendingManagerInvitations(): HasMany
    {
        return $this->hasMany(ManagerInvitation::class)
            ->where('status', 'pending')
            ->where('expires_at', '>', now());
    }


    // ============================================
    // FEATURES & AMENITIES
    // ============================================
    
    /**
     * Payment Methods
     */
    public function paymentMethods(): BelongsToMany
    {
        return $this->belongsToMany(PaymentMethod::class, 'business_payment_method');
    }

    /**
     * Business Amenities
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'business_amenity');
    }

    /**
     * Business FAQs
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(FAQ::class)->ordered();
    }

    /**
     * Active FAQs only
     */
    public function activeFaqs(): HasMany
    {
        return $this->hasMany(FAQ::class)
            ->where('is_active', true)
            ->ordered();
    }

    // ============================================
    // REVIEWS & RATINGS
    // ============================================
    
    /**
     * Business Reviews (Polymorphic)
     * Reviews attached to this business
     */
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    // ============================================
    // HELPER METHODS
    // ============================================


    // ============================================
    // SEO & CONTENT QUALITY METHODS
    // ============================================

    /**
     * Get the URL for this business
     * Format: /{business-type}/{business-slug}
     */
    public function getUrl()
    {
        if (!$this->businessType) {
            return route('businesses.show', [
                'businessType' => 'business',
                'slug' => $this->slug
            ]);
        }
        
        return route('businesses.show', [
            'businessType' => $this->businessType->slug,
            'slug' => $this->slug
        ]);
    }

    /**
     * Get the canonical URL for this business
     */
    public function getCanonicalUrl()
    {
        // If custom canonical URL is set, use it
        if ($this->canonical_url) {
            return $this->canonical_url;
        }

        // Default: self-referencing with business type
        return $this->getUrl();
    }

    /**
     * Get meta title (auto-generate if not set)
     */
    public function getMetaTitleAttribute($value)
    {
        if ($value) {
            return $value;
        }

        // Auto-generate: "Business Name | City, State"
        return "{$this->business_name} | {$this->city}, {$this->state}";
    }

    /**
     * Get meta description (auto-generate if not set)
     */
    public function getMetaDescriptionAttribute($value)
    {
        if ($value) {
            return $value;
        }

        // Auto-generate from business description
        return \Illuminate\Support\Str::limit($this->description, 155);
    }

    /**
     * Check if business has sufficient unique content
     */
    public function hasUniqueContent()
    {
        return $this->has_unique_content;
    }

    /**
     * Generate Schema.org JSON-LD markup for business
     */
    public function getSchemaMarkup()
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $this->businessType->schema_type ?? 'LocalBusiness',
            'name' => $this->business_name,
            'description' => $this->description,
            'url' => route('business.show', $this->slug),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $this->address,
                'addressLocality' => $this->city,
                'addressRegion' => $this->state,
                'addressCountry' => 'NG',
            ],
        ];

        // Add contact info
        if ($this->phone) {
            $schema['telephone'] = $this->phone;
        }

        if ($this->email) {
            $schema['email'] = $this->email;
        }

        if ($this->website) {
            $schema['url'] = $this->website;
        }

        // Add geo coordinates
        if ($this->latitude && $this->longitude) {
            $schema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ];
        }

        // Add rating
        if ($this->avg_rating && $this->total_reviews > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $this->avg_rating,
                'reviewCount' => $this->total_reviews,
            ];
        }

        // Add opening hours
        if ($this->business_hours) {
            $openingHours = [];
            foreach ($this->business_hours as $day => $hours) {
                if (!($hours['closed'] ?? false) && isset($hours['open'], $hours['close'])) {
                    $dayAbbr = ucfirst(substr($day, 0, 2));
                    $openingHours[] = "{$dayAbbr} {$hours['open']}-{$hours['close']}";
                }
            }
            if (!empty($openingHours)) {
                $schema['openingHours'] = $openingHours;
            }
        }

        // Add logo
        if ($this->logo) {
            $schema['logo'] = asset('storage/' . $this->logo);
        }

        // Add image gallery
        if ($this->gallery && count($this->gallery) > 0) {
            $schema['image'] = array_map(fn($img) => asset('storage/' . $img), $this->gallery);
        }

        return $schema;
    }

    /**
     * Get content quality score (0-100)
     */
    public function getContentQualityScore()
    {
        $score = 0;

        // Has description (20 points)
        if ($this->description && strlen($this->description) >= 100) {
            $score += 20;
        } elseif ($this->description) {
            $score += 10;
        }

        // Has unique content flag (30 points)
        if ($this->has_unique_content) {
            $score += 30;
        }

        // Has photos (10 points)
        if ($this->gallery && count($this->gallery) >= 3) {
            $score += 10;
        } elseif ($this->gallery && count($this->gallery) > 0) {
            $score += 5;
        }

        // Has reviews (20 points)
        if ($this->total_reviews >= 5) {
            $score += 20;
        } elseif ($this->total_reviews > 0) {
            $score += 10;
        }

        // Has unique features (10 points)
        if ($this->unique_features && count($this->unique_features) >= 3) {
            $score += 10;
        } elseif ($this->unique_features && count($this->unique_features) > 0) {
            $score += 5;
        }

        // Has nearby landmarks (10 points)
        if ($this->nearby_landmarks && strlen($this->nearby_landmarks) >= 50) {
            $score += 10;
        } elseif ($this->nearby_landmarks) {
            $score += 5;
        }

        return $score;
    }

    /**
     * Check if business is open now
     */
    public function isOpen()
    {
        if (!$this->business_hours) {
            return null;
        }

        $day = strtolower(now()->format('l'));
        $currentTime = now()->format('H:i');

        if (!isset($this->business_hours[$day])) {
            return false;
        }

        $hours = $this->business_hours[$day];
        
        if ($hours['closed'] ?? false) {
            return false;
        }

        return $currentTime >= $hours['open'] && $currentTime <= $hours['close'];
    }
    
    // ============================================
    // ANALYTICS HELPER METHODS
    // ============================================

    /**
     * Get total views count
     */
    public function getTotalViewsCount(): int
    {
        return $this->views()->count();
    }

    /**
     * Get total interactions count
     */
    public function getTotalInteractionsCount(): int
    {
        return $this->interactions()->count();
    }

    /**
     * Get saves/bookmarks count
     */
    public function getTotalSavesCount(): int
    {
        return $this->savedByUsers()->count();
    }

    /**
     * Get interaction breakdown by type (calls, whatsapp, email, etc.)
     */
    public function getInteractionBreakdown(): array
    {
        return $this->interactions()
            ->selectRaw('interaction_type, COUNT(*) as count')
            ->groupBy('interaction_type')
            ->pluck('count', 'interaction_type')
            ->toArray();
    }

    /**
     * Get views by referral source
     */
    public function getViewsBySource(): array
    {
        return $this->views()
            ->selectRaw('referral_source, COUNT(*) as count')
            ->groupBy('referral_source')
            ->pluck('count', 'referral_source')
            ->toArray();
    }

    /**
     * Check if business has any analytics data
     */
    public function hasAnalyticsData(): bool
    {
        return $this->views()->exists() 
            || $this->interactions()->exists();
    }

    /**
     * Record a page view for business
     * 
     * @param ReferralSource|string|null $referralSource Source of traffic
     * @return BusinessView
     */
    public function recordView(ReferralSource|string|null $referralSource = null)
    {
        return BusinessView::recordView(
            businessId: $this->id,
            referralSource: $referralSource
        );
    }

    /**
     * Record an interaction for business
     * 
     * @param InteractionType|string $type Type of interaction (call, whatsapp, email, etc.)
     * @param ReferralSource|string|null $referralSource Source of traffic
     * @param int|null $userId User ID if logged in
     * @return BusinessInteraction
     */
    public function recordInteraction(
        InteractionType|string $type, 
        ReferralSource|string|null $referralSource = null, 
        ?int $userId = null
    ) {
        return BusinessInteraction::recordInteraction(
            businessId: $this->id,
            type: $type,
            referralSource: $referralSource,
            userId: $userId
        );
    }

    /**
     * Record an impression when business listing is visible
     * 
     * @param PageType|string $pageType Where listing is visible
     * @param ReferralSource|string|null $referralSource Source of traffic
     * @return BusinessImpression
     */
    public function recordImpression(
        PageType|string $pageType, 
        ReferralSource|string|null $referralSource = null
    ) {
        return BusinessImpression::recordImpression(
            businessId: $this->id,
            pageType: $pageType,
            referralSource: $referralSource
        );
    }

    /**
     * Record a click when someone visits business detail page
     * Cookie-based: only records once per person (until cookie expires)
     * 
     * @param ReferralSource|string|null $referralSource Source of traffic
     * @param PageType|string|null $sourcePageType Where click came from
     * @return BusinessClick|null Returns null if click already recorded (duplicate)
     */
    public function recordClick(
        ReferralSource|string|null $referralSource = null, 
        PageType|string|null $sourcePageType = null
    ): ?BusinessClick {
        return BusinessClick::recordClick(
            businessId: $this->id,
            referralSource: $referralSource,
            sourcePageType: $sourcePageType
        );
    }

    /**
     * Get average rating
     */
    public function getOverallRating(): float
    {
        return round($this->reviews()->avg('rating') ?? 0, 2);
    }

    /**
     * Update aggregate statistics for this business
     * Called after reviews are added/updated or leads are created
     */
    public function updateAggregateStats()
    {
        $this->update([
            'avg_rating' => $this->getOverallRating(),
            'total_reviews' => $this->reviews()->count(),
            'total_leads' => $this->leads()->count(),
            'total_views' => $this->getTotalViewsCount(),
            'total_saves' => $this->getTotalSavesCount(),
        ]);
        
        return $this;
    }

    // ============================================
    // STATUS CHECKS
    // ============================================

    /**
     * Check if business is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if business is verified
     */
    public function isVerified(): bool
    {
        return $this->is_verified === true;
    }

    /**
     * Check if business is premium
     */
    public function isPremium(): bool
    {
        return $this->is_premium === true && 
               ($this->premium_until === null || $this->premium_until->isFuture());
    }

    /**
     * Check if business is claimed
     */
    public function isClaimed(): bool
    {
        return $this->is_claimed === true;
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope for active businesses
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for verified businesses
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for premium businesses
     */
    public function scopePremium($query)
    {
        return $query->where('is_premium', true)
                     ->where(function ($q) {
                         $q->whereNull('premium_until')
                           ->orWhere('premium_until', '>', now());
                     });
    }


    /**
     * Scope for businesses by type
     */
    public function scopeByType($query, $businessTypeId)
    {
        return $query->where('business_type_id', $businessTypeId);
    }

    /**
     * Scope for businesses by location
     */
    public function scopeByLocation($query, $stateId = null, $cityId = null)
    {
        if ($stateId) {
            $query->where('state_location_id', $stateId);
        }
        
        if ($cityId) {
            $query->where('city_location_id', $cityId);
        }
        
        return $query;
    }

    /**
     * Scope for claimed businesses
     */
    public function scopeClaimed($query)
    {
        return $query->where('is_claimed', true);
    }

    /**
     * Scope for unclaimed businesses
     */
    public function scopeUnclaimed($query)
    {
        return $query->where('is_claimed', false);
    }

    // ============================================
    // ACCESSORS & MUTATORS
    // ============================================

    /**
     * Get the full address
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->area,
            $this->city,
            $this->state,
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Get the business age in years
     */
    public function getAgeAttribute(): ?int
    {
        return $this->years_in_business;
    }

    // ============================================
    // BOOT METHOD
    // ============================================

    protected static function booted()
    {
        // Auto-generate slug on creation if not provided
        static::creating(function ($business) {
            if (empty($business->slug)) {
                $business->slug = \Illuminate\Support\Str::slug($business->business_name);
            }
        });
    }
}