<?php
// ============================================
// app/Models/User.php - COMPLETE UPDATED VERSION
// Includes manager relationships and complete role system
// ============================================

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        // Social auth (customer only)
        'oauth_provider',
        'oauth_provider_id',
        'avatar',
        'bio',
        'is_active',
        'is_banned',
        'ban_reason',
        'banned_at',
        'last_login_at',
        'last_login_ip',
        'referral_code',
        'referred_by',
        'is_branch_manager',
        'managing_branches_count',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'role' => UserRole::class,
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'is_banned' => 'boolean',
        'banned_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_branch_manager' => 'boolean',
    ];

    // ============================================
    // FILAMENT MULTI-PANEL ACCESS CONTROL
    // ============================================

    /**
     * Determine which Filament panel(s) the user can access
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Check if user is banned or inactive
        if ($this->is_banned || !$this->is_active) {
            return false;
        }

        // Check panel access based on role
        return match($panel->getId()) {
            'admin' => $this->isAdmin() || $this->isModerator(),
            'business' => $this->isBusinessOwner() || $this->isBusinessManager(),
            'customer' => $this->isCustomer(),
            default => false,
        };
    }

    /**
     * Get user's display name for Filament
     */
    public function getFilamentName(): string
    {
        return $this->name;
    }

    /**
     * Get user's avatar for Filament
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar ? asset('storage/' . $this->avatar) : null;
    }

    /**
     * Get the default panel for user's role
     */
    public function getDefaultPanel(): string
    {
        return match(true) {
            $this->isAdmin() || $this->isModerator() => 'admin',
            $this->isBusinessOwner() => 'business',
            $this->isCustomer() => 'customer',
            default => 'customer',
        };
    }

    /**
     * Get panel URL for this user
     */
    public function getPanelUrl(): string
    {
        return match($this->getDefaultPanel()) {
            'admin' => '/admin',
            'business' => '/dashboard',
            'customer' => '/customer',
            default => '/customer',
        };
    }

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function businesses()
    {
        return $this->hasMany(Business::class);
    }

    public function claimedBusinesses()
    {
        return $this->hasMany(Business::class, 'claimed_by');
    }

    public function businessClaims()
    {
        return $this->hasMany(BusinessClaim::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    
    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function savedBusinesses()
    {
        return $this->belongsToMany(Business::class, 'saved_businesses', 'user_id', 'business_id')
            ->withTimestamps();
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get wallet for a specific business
     * @param int $businessId
     * @return Wallet|null
     */
    public function walletForBusiness(int $businessId): ?Wallet
    {
        return Wallet::where('business_id', $businessId)
            ->where('user_id', $this->id)
            ->first();
    }

    /**
     * All wallets for businesses this user owns or manages
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function wallets()
    {
        $businessIds = $this->businesses()->pluck('id')
            ->merge($this->managedBusinesses()->pluck('id'))
            ->unique();
        
        return Wallet::whereIn('business_id', $businessIds)
            ->where('user_id', $this->id)
            ->get();
    }

    /**
     * @deprecated Use walletForBusiness() or wallets() instead. Wallets are now business-scoped.
     */
    public function wallet()
    {
        // Return wallet for first business (for backward compatibility)
        $business = $this->businesses()->first() ?? $this->managedBusinesses()->first();
        return $business ? $this->walletForBusiness($business->id) : null;
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get transactions for a specific business
     * @param int $businessId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function transactionsForBusiness(int $businessId)
    {
        return Transaction::where('business_id', $businessId)
            ->where('user_id', $this->id)
            ->get();
    }

    /**
     * All transactions for businesses this user owns or manages
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function transactions()
    {
        $businessIds = $this->businesses()->pluck('id')
            ->merge($this->managedBusinesses()->pluck('id'))
            ->unique();
        
        return Transaction::whereIn('business_id', $businessIds)
            ->where('user_id', $this->id)
            ->get();
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    /**
     * Customer referral wallet (commission balance; customers who refer businesses)
     */
    public function customerReferralWallet()
    {
        return $this->hasOne(CustomerReferralWallet::class);
    }

    /**
     * Customer referrals (businesses this customer referred; 10% commission on their payments)
     */
    public function customerReferrals()
    {
        return $this->hasMany(CustomerReferral::class, 'referrer_user_id');
    }

    /**
     * Get or create customer referral wallet (for commission balance).
     */
    public function getOrCreateCustomerReferralWallet(): CustomerReferralWallet
    {
        return CustomerReferralWallet::firstOrCreate(
            ['user_id' => $this->id],
            ['balance' => 0, 'currency' => 'NGN']
        );
    }

    /**
     * Business Manager assignments
     */
    public function businessManagerAssignments()
    {
        return $this->hasMany(BusinessManager::class);
    }

    public function activeBusinessManagers()
    {
        return $this->hasMany(BusinessManager::class)->where('is_active', true);
    }

    /**
     * Businesses this user manages
     */
    public function managedBusinesses()
    {
        return $this->belongsToMany(
            Business::class,
            'business_managers',
            'user_id',
            'business_id'
        )
        ->using(BusinessManager::class)
        ->withPivot(['position', 'permissions', 'is_active', 'is_primary', 'joined_at'])
        ->wherePivot('is_active', true);
    }

    public function managerInvitations()
    {
        return $this->hasMany(ManagerInvitation::class, 'user_id');
    }

    public function pendingManagerInvitations()
    {
        return $this->hasMany(ManagerInvitation::class, 'user_id')
            ->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    public function sentManagerInvitations()
    {
        return $this->hasMany(ManagerInvitation::class, 'invited_by');
    }
  
    /**
 * User Preferences
 */
public function preferences()
{
    return $this->hasOne(UserPreference::class);
}

/**
 * Get preferences or create default ones
 */
public function getPreferencesAttribute()
{
    return UserPreference::getForUser($this->id);
}

    // ============================================
    // ROLE CHECK METHODS
    // ============================================

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isModerator(): bool
    {
        return $this->role === UserRole::MODERATOR;
    }

    public function isBusinessOwner(): bool
    {
        return $this->role === UserRole::BUSINESS_OWNER;
    }


    public function isCustomer(): bool
    {
        return $this->role === UserRole::CUSTOMER;
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [UserRole::ADMIN, UserRole::MODERATOR]);
    }

    public function canManageBusinesses(): bool
    {
        return $this->isBusinessOwner() || $this->isBusinessManager();
    }

    /**
     * Check if user is a business manager (manages at least one business)
     */
    public function isBusinessManager(): bool
    {
        return $this->activeBusinessManagers()->exists();
    }

    /**
     * Check if user manages a specific business
     */
    public function managesBusiness(int $businessId): bool
    {
        return $this->activeBusinessManagers()
            ->where('business_id', $businessId)
            ->exists();
    }

    /**
     * Get BusinessManager relationship for a specific business
     */
    public function getBusinessManagerFor(int $businessId): ?BusinessManager
    {
        return $this->activeBusinessManagers()
            ->where('business_id', $businessId)
            ->first();
    }

    // ============================================
    // PERMISSION SYSTEM
    // ============================================

    public function can($ability, $arguments = []): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return match($ability) {
            'create-business' => $this->isBusinessOwner(),
            'edit-business' => $this->canEditBusiness($arguments),
            'delete-business' => $this->canDeleteBusiness($arguments),
            'manage-products' => $this->canManageProducts($arguments),
            'respond-to-review' => $this->canRespondToReview($arguments),
            'delete-review' => $this->isAdmin(),
            'view-leads' => $this->canViewLeads($arguments),
            'respond-to-leads' => $this->canRespondToLeads($arguments),
            'view-analytics' => $this->canViewAnalytics($arguments),
            'access-financials' => $this->canAccessFinancials($arguments),
            'manage-staff' => $this->canManageStaff($arguments),
            'moderate-content' => $this->isStaff(),
            'approve-claims' => $this->isAdmin(),
            'approve-verifications' => $this->isAdmin(),
            default => false,
        };
    }

    private function canEditBusiness($business): bool
    {
        if ($this->isAdmin()) return true;
        
        // Business owner
        if (is_object($business) && $business->user_id === $this->id) {
            return true;
        }
        
        // Business manager with permission
        if (is_object($business)) {
            $manager = $this->getBusinessManagerFor($business->id);
            if ($manager && $manager->hasPermission('can_edit_business')) {
                return true;
            }
        }
        
        return false;
    }

    private function canManageProducts($business): bool
    {
        if ($this->isAdmin()) return true;
        
        // Business owner
        if (is_object($business) && $business->user_id === $this->id) {
            return true;
        }
        
        // Business manager with permission
        if (is_object($business)) {
            $manager = $this->getBusinessManagerFor($business->id);
            if ($manager && $manager->hasPermission('can_manage_products')) {
                return true;
            }
        }
        
        return false;
    }

    private function canViewLeads($business): bool
    {
        if ($this->isAdmin()) return true;
        
        // Business owner
        if (is_object($business) && $business->user_id === $this->id) {
            return true;
        }
        
        // Business manager with permission
        if (is_object($business)) {
            $manager = $this->getBusinessManagerFor($business->id);
            if ($manager && $manager->hasPermission('can_view_leads')) {
                return true;
            }
        }
        
        return false;
    }

    private function canRespondToLeads($business): bool
    {
        if ($this->isAdmin()) return true;
        
        // Business owner
        if (is_object($business) && $business->user_id === $this->id) {
            return true;
        }
        
        // Business manager with permission
        if (is_object($business)) {
            $manager = $this->getBusinessManagerFor($business->id);
            if ($manager && $manager->hasPermission('can_respond_to_leads')) {
                return true;
            }
        }
        
        return false;
    }

    private function canRespondToReview($review): bool
    {
        if ($this->isAdmin()) return true;
        
        // Get business from review
        $business = $review->reviewable;
        if (!$business || !($business instanceof Business)) {
            return false;
        }
        
        // Business owner
        if ($business->user_id === $this->id) {
            return true;
        }
        
        // Business manager with permission
        $manager = $this->getBusinessManagerFor($business->id);
        if ($manager && $manager->hasPermission('can_respond_to_reviews')) {
            return true;
        }
        
        return false;
    }

    private function canViewAnalytics($business): bool
    {
        if ($this->isAdmin()) return true;
        
        // Business owner
        if (is_object($business) && $business->user_id === $this->id) {
            return true;
        }
        
        // Business manager with permission
        if (is_object($business)) {
            $manager = $this->getBusinessManagerFor($business->id);
            if ($manager && $manager->hasPermission('can_view_analytics')) {
                return true;
            }
        }
        
        return false;
    }

    private function canAccessFinancials($business): bool
    {
        if ($this->isAdmin()) return true;
        
        // Business owner
        if (is_object($business) && $business->user_id === $this->id) {
            return true;
        }
        
        // Business manager with permission
        if (is_object($business)) {
            $manager = $this->getBusinessManagerFor($business->id);
            if ($manager && $manager->hasPermission('can_access_financials')) {
                return true;
            }
        }
        
        return false;
    }

    private function canManageStaff($business): bool
    {
        if ($this->isAdmin()) return true;
        
        // Business owner
        if (is_object($business) && $business->user_id === $this->id) {
            return true;
        }
        
        // Business manager with permission
        if (is_object($business)) {
            $manager = $this->getBusinessManagerFor($business->id);
            if ($manager && $manager->hasPermission('can_manage_staff')) {
                return true;
            }
        }
        
        return false;
    }

    private function canDeleteBusiness($business): bool
    {
        return $this->isAdmin() || 
               ($this->isBusinessOwner() && $business->user_id === $this->id);
    }

    // ============================================
    // ROLE ASSIGNMENT METHODS
    // ============================================

    public function assignRole(string|UserRole $role): void
    {
        $this->update(['role' => $role]);
    }

    public function promoteToBusinessOwner(): void
    {
        if ($this->isCustomer()) {
            $this->assignRole(UserRole::BUSINESS_OWNER);
        }
    }

    public function promoteToModerator(): void
    {
        if (!$this->isAdmin()) {
            $this->assignRole(UserRole::MODERATOR);
        }
    }

    public function promoteToAdmin(): void
    {
        $this->assignRole(UserRole::ADMIN);
    }

    public function demoteToCustomer(): void
    {
        if (!$this->isAdmin()) {
            $this->assignRole(UserRole::CUSTOMER);
        }
    }

    // ============================================
    // Email Verification
    // ============================================

    /**
     * Send the email verification notification.
     * Override to use Filament's panel-aware verification URLs.
     */
    public function sendEmailVerificationNotification(): void
    {
        // Determine which panel the user should verify from based on their role
        $panelId = match(true) {
            $this->isCustomer() => 'customer',
            $this->isBusinessOwner() => 'business',
            default => 'customer', // fallback
        };

        // Get the Filament panel instance
        $panel = \Filament\Facades\Filament::getPanel($panelId);
        
        // Get Filament's verification URL for this user
        $verificationUrl = $panel->getVerifyEmailUrl($this);
        
        // Create and send a custom verification notification with Filament's URL
        $this->notify(new \App\Notifications\VerifyEmailNotification($verificationUrl));
    }

    // ============================================
    // Other Helper Methods
    // ============================================

    public function hasActiveSubscription()
    {
        return $this->subscription()->exists();
    }

}