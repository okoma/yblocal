<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add critical performance indexes for frequently queried columns
     */
    public function up(): void
    {
        // TRANSACTIONS table - heavily queried for user transactions
        Schema::table('transactions', function (Blueprint $table) {
            // Composite index for user transactions query
            $table->index(['user_id', 'status', 'created_at'], 'transactions_user_status_date_idx');
            
            // Index for payment method filtering
            $table->index(['payment_method', 'status'], 'transactions_method_status_idx');
            
            // Index for transactionable polymorphic relationship queries
            $table->index(['transactionable_type', 'transactionable_id'], 'transactions_transactionable_idx');
        });

        // SUBSCRIPTIONS table - frequently filtered by status and dates
        Schema::table('subscriptions', function (Blueprint $table) {
            // Composite index for active subscription queries
            $table->index(['user_id', 'status', 'ends_at'], 'subscriptions_user_status_ends_idx');
            
            // Index for business subscriptions
            $table->index(['business_id', 'status'], 'subscriptions_business_status_idx');
            
            // Index for expiring subscriptions (used in notifications)
            $table->index(['status', 'ends_at'], 'subscriptions_expiring_idx');
            
            // Index for auto-renewal queries
            $table->index(['auto_renew', 'ends_at'], 'subscriptions_auto_renew_idx');
        });

        // BUSINESSES table - main entity with many relationships
        Schema::table('businesses', function (Blueprint $table) {
            // Composite index for owner queries
            $table->index(['user_id', 'status'], 'businesses_owner_status_idx');
            
            // Index for location-based queries
            $table->index(['state_location_id', 'city_location_id', 'status'], 'businesses_location_status_idx');
            
            // Index for verification status queries
            $table->index(['is_verified', 'status'], 'businesses_verified_status_idx');
            
            // Index for premium businesses
            $table->index(['is_premium', 'premium_until', 'status'], 'businesses_premium_idx');
            
            // Index for business type filtering
            $table->index(['business_type_id', 'status'], 'businesses_type_status_idx');
        });

        // BUSINESS_VIEWS table - analytics queries
        Schema::table('business_views', function (Blueprint $table) {
            // Composite index for business analytics
            $table->index(['business_id', 'view_date'], 'business_views_analytics_idx');
            
            // Index for referral source analysis
            $table->index(['business_id', 'referral_source'], 'business_views_referral_idx');
        });

        // BUSINESS_INTERACTIONS table - user engagement tracking
        Schema::table('business_interactions', function (Blueprint $table) {
            // Composite index for business interaction analytics
            $table->index(['business_id', 'interaction_type', 'created_at'], 'business_interactions_analytics_idx');
        });

        // BUSINESS_MANAGERS table - permission checks
        Schema::table('business_managers', function (Blueprint $table) {
            // Composite index for active manager lookups
            $table->index(['business_id', 'user_id', 'is_active'], 'business_managers_lookup_idx');
            
            // Index for user's managed businesses
            $table->index(['user_id', 'is_active'], 'business_managers_user_active_idx');
        });

        // WALLET_TRANSACTIONS table - financial history queries
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // Composite index for wallet transaction history
            $table->index(['wallet_id', 'type', 'created_at'], 'wallet_transactions_history_idx');
            
            // Index for pending transactions
            $table->index(['wallet_id', 'created_at'], 'wallet_transactions_recent_idx');
        });

        // AD_CAMPAIGNS table - advertising management
        Schema::table('ad_campaigns', function (Blueprint $table) {
            // Composite index for active campaigns
            $table->index(['business_id', 'is_active', 'starts_at'], 'ad_campaigns_active_idx');
            
            // Index for payment status
            $table->index(['is_paid', 'is_active'], 'ad_campaigns_payment_idx');
        });

        // REVIEWS table - product/business reviews
        Schema::table('reviews', function (Blueprint $table) {
            // Composite index for reviewable entity
            $table->index(['reviewable_type', 'reviewable_id', 'is_approved'], 'reviews_reviewable_approved_idx');
            
            // Index for user reviews
            $table->index(['user_id', 'created_at'], 'reviews_user_recent_idx');
        });

        // NOTIFICATIONS table - user notification queries
        Schema::table('notifications', function (Blueprint $table) {
            // Composite index for unread notifications
            $table->index(['user_id', 'is_read', 'created_at'], 'notifications_user_unread_idx');
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_user_status_date_idx');
            $table->dropIndex('transactions_method_status_idx');
            $table->dropIndex('transactions_transactionable_idx');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex('subscriptions_user_status_ends_idx');
            $table->dropIndex('subscriptions_business_status_idx');
            $table->dropIndex('subscriptions_expiring_idx');
            $table->dropIndex('subscriptions_auto_renew_idx');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex('businesses_owner_status_idx');
            $table->dropIndex('businesses_location_status_idx');
            $table->dropIndex('businesses_verified_status_idx');
            $table->dropIndex('businesses_premium_idx');
            $table->dropIndex('businesses_type_status_idx');
        });

        Schema::table('business_views', function (Blueprint $table) {
            $table->dropIndex('business_views_analytics_idx');
            $table->dropIndex('business_views_referral_idx');
        });

        Schema::table('business_interactions', function (Blueprint $table) {
            $table->dropIndex('business_interactions_analytics_idx');
        });

        Schema::table('business_managers', function (Blueprint $table) {
            $table->dropIndex('business_managers_lookup_idx');
            $table->dropIndex('business_managers_user_active_idx');
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropIndex('wallet_transactions_history_idx');
            $table->dropIndex('wallet_transactions_recent_idx');
        });

        Schema::table('ad_campaigns', function (Blueprint $table) {
            $table->dropIndex('ad_campaigns_active_idx');
            $table->dropIndex('ad_campaigns_payment_idx');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_reviewable_approved_idx');
            $table->dropIndex('reviews_user_recent_idx');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_user_unread_idx');
        });
    }
};
