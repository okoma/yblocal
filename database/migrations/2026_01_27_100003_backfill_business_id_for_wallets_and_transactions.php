<?php

// ============================================
// database/migrations/2026_01_27_100003_backfill_business_id_for_wallets_and_transactions.php
// Backfill business_id for existing wallets, wallet_transactions, and transactions
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill wallets: Assign business_id from user's first business or managed business
        DB::statement("
            UPDATE wallets w
            INNER JOIN users u ON w.user_id = u.id
            LEFT JOIN businesses b_owned ON b_owned.user_id = u.id AND b_owned.deleted_at IS NULL
            LEFT JOIN business_managers bm ON bm.user_id = u.id AND bm.is_active = 1
            LEFT JOIN businesses b_managed ON b_managed.id = bm.business_id AND b_managed.deleted_at IS NULL
            SET w.business_id = COALESCE(
                (SELECT id FROM businesses WHERE user_id = u.id AND deleted_at IS NULL ORDER BY id LIMIT 1),
                (SELECT business_id FROM business_managers WHERE user_id = u.id AND is_active = 1 ORDER BY id LIMIT 1),
                NULL
            )
            WHERE w.business_id IS NULL
        ");

        // Backfill wallet_transactions: Get business_id from wallet
        DB::statement("
            UPDATE wallet_transactions wt
            INNER JOIN wallets w ON wt.wallet_id = w.id
            SET wt.business_id = w.business_id
            WHERE wt.business_id IS NULL AND w.business_id IS NOT NULL
        ");

        // Backfill transactions: Get business_id from transactionable (Subscription or AdCampaign)
        // For Subscription transactions
        DB::statement("
            UPDATE transactions t
            INNER JOIN subscriptions s ON t.transactionable_type = 'App\\\\Models\\\\Subscription' 
                AND t.transactionable_id = s.id
            SET t.business_id = s.business_id
            WHERE t.business_id IS NULL AND s.business_id IS NOT NULL
        ");

        // For AdCampaign transactions
        DB::statement("
            UPDATE transactions t
            INNER JOIN ad_campaigns ac ON t.transactionable_type = 'App\\\\Models\\\\AdCampaign' 
                AND t.transactionable_id = ac.id
            SET t.business_id = ac.business_id
            WHERE t.business_id IS NULL AND ac.business_id IS NOT NULL
        ");

        // For Wallet transactions (wallet funding)
        DB::statement("
            UPDATE transactions t
            INNER JOIN wallets w ON t.transactionable_type = 'App\\\\Models\\\\Wallet' 
                AND t.transactionable_id = w.id
            SET t.business_id = w.business_id
            WHERE t.business_id IS NULL AND w.business_id IS NOT NULL
        ");

        // For transactions without a valid business_id, try to get from user
        DB::statement("
            UPDATE transactions t
            INNER JOIN users u ON t.user_id = u.id
            LEFT JOIN businesses b_owned ON b_owned.user_id = u.id AND b_owned.deleted_at IS NULL
            LEFT JOIN business_managers bm ON bm.user_id = u.id AND bm.is_active = 1
            LEFT JOIN businesses b_managed ON b_managed.id = bm.business_id AND b_managed.deleted_at IS NULL
            SET t.business_id = COALESCE(
                (SELECT id FROM businesses WHERE user_id = u.id AND deleted_at IS NULL ORDER BY id LIMIT 1),
                (SELECT business_id FROM business_managers WHERE user_id = u.id AND is_active = 1 ORDER BY id LIMIT 1),
                NULL
            )
            WHERE t.business_id IS NULL
        ");
    }

    public function down(): void
    {
        // Set all business_id to NULL (reversible)
        DB::table('wallets')->update(['business_id' => null]);
        DB::table('wallet_transactions')->update(['business_id' => null]);
        DB::table('transactions')->update(['business_id' => null]);
    }
};
