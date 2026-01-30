<?php

namespace App\Providers;

use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\Lead;
use App\Models\Review;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Observers\BusinessActivityObserver;
use App\Observers\BusinessClaimObserver;
use App\Observers\BusinessObserver;
use App\Observers\LeadObserver;
use App\Observers\ReviewObserver;
use App\Observers\SubscriptionActivityObserver;
use App\Observers\TransactionActivityObserver;
use App\Observers\UserObserver;
use App\Observers\WalletActivityObserver;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use App\Listeners\SyncLaravelNotificationToCustomTable;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use SocialiteProviders\Manager\SocialiteWasCalled;

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
        // Configure rate limiting
        $this->configureRateLimiting();
        
        // Register model observers for automatic notifications
        
        // Customer ↔ Business interactions
        Review::observe(ReviewObserver::class);
        Lead::observe(LeadObserver::class);
        
        // Business management
        Business::observe(BusinessObserver::class);
        Business::observe(BusinessActivityObserver::class);
        BusinessClaim::observe(BusinessClaimObserver::class);

        // Audit trail observers
        Wallet::observe(WalletActivityObserver::class);
        Transaction::observe(TransactionActivityObserver::class);
        Subscription::observe(SubscriptionActivityObserver::class);
        
        // User lifecycle
        User::observe(UserObserver::class);

        // Register listener to sync Laravel notifications to custom notifications table
        Event::listen(
            NotificationSent::class,
            SyncLaravelNotificationToCustomTable::class
        );

        // Register SocialiteProviders (Apple)
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('apple', \SocialiteProviders\Apple\Provider::class);
        });
    }

    /**
     * Configure rate limiting for the application.
     */
    protected function configureRateLimiting(): void
    {
        \Illuminate\Support\Facades\RateLimiter::for('api', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('discovery', function ($request) {
            // Public discovery endpoints - 120 requests per minute per IP
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(120)->by($request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('search', function ($request) {
            // Search endpoints - 60 requests per minute per IP
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('auth', function ($request) {
            // Auth endpoints (login, register) - 10 attempts per minute per IP
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by($request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('webhooks', function ($request) {
            // Webhooks - 300 requests per minute per IP (payment gateways may send bursts)
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(300)->by($request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('payment', function ($request) {
            // Payment initiation - 20 requests per minute per user
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
    }
}
