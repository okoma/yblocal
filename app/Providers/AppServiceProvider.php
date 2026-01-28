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
use App\Listeners\SyncLaravelNotificationToCustomTable;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

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

        // Register listener to sync Laravel notifications to custom notifications table
        Event::listen(
            NotificationSent::class,
            SyncLaravelNotificationToCustomTable::class
        );
    }
}
