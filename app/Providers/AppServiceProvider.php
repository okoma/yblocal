<?php

namespace App\Providers;

use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\Lead;
use App\Models\Review;
use App\Models\User;
use App\Observers\BusinessClaimObserver;
use App\Observers\BusinessObserver;
use App\Observers\LeadObserver;
use App\Observers\ReviewObserver;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers for automatic notifications
        
        // Customer ↔ Business interactions
        Review::observe(ReviewObserver::class);
        Lead::observe(LeadObserver::class);
        
        // Business management
        Business::observe(BusinessObserver::class);
        BusinessClaim::observe(BusinessClaimObserver::class);
        
        // User lifecycle
        User::observe(UserObserver::class);
    }
}
