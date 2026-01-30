<?php

namespace App\Providers;

use App\Models\Business;
use App\Models\CustomerReferralWallet;
use App\Models\QuoteRequest;
use App\Models\QuoteResponse;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Policies\BusinessPolicy;
use App\Policies\CustomerReferralWalletPolicy;
use App\Policies\QuoteRequestPolicy;
use App\Policies\QuoteResponsePolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\TransactionPolicy;
use App\Policies\WalletPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Business::class => BusinessPolicy::class,
        Wallet::class => WalletPolicy::class,
        Transaction::class => TransactionPolicy::class,
        QuoteRequest::class => QuoteRequestPolicy::class,
        QuoteResponse::class => QuoteResponsePolicy::class,
        Subscription::class => SubscriptionPolicy::class,
        CustomerReferralWallet::class => CustomerReferralWalletPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Register policies
        $this->registerPolicies();

        // Admin gate - full access to everything
        Gate::before(function ($user, $ability) {
            if ($user->isAdmin()) {
                return true;
            }
        });

        // Define custom gates for specific permissions
        Gate::define('manage-users', function ($user) {
            return $user->isAdmin() || $user->isModerator();
        });

        Gate::define('manage-verifications', function ($user) {
            return $user->isAdmin() || $user->isModerator();
        });

        Gate::define('view-analytics', function ($user) {
            return $user->isAdmin() || $user->isModerator() || $user->isBusinessOwner();
        });

        Gate::define('process-refunds', function ($user) {
            return $user->isAdmin();
        });

        Gate::define('approve-withdrawals', function ($user) {
            return $user->isAdmin();
        });

        Gate::define('manage-system-settings', function ($user) {
            return $user->isAdmin();
        });

        Gate::define('export-data', function ($user) {
            return $user->isAdmin() || $user->isModerator();
        });

        Gate::define('view-reports', function ($user) {
            return $user->isAdmin() || $user->isModerator();
        });

        Gate::define('ban-users', function ($user) {
            return $user->isAdmin();
        });

        Gate::define('manage-referral-system', function ($user) {
            return $user->isAdmin();
        });
    }
}
