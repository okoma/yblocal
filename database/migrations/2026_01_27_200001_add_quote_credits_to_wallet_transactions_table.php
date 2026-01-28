<?php

// ============================================
// database/migrations/2026_01_27_200001_add_quote_credits_to_wallet_transactions_table.php
// Add quote_credits tracking to wallet_transactions table
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // Add quote credits tracking (similar to ad credits)
            $table->integer('quote_credits')->default(0)->after('credits');
            $table->integer('quote_credits_before')->default(0)->after('credits_after');
            $table->integer('quote_credits_after')->default(0)->after('quote_credits_before');
            
            // Add quote_submission type to enum
            DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN type ENUM('deposit', 'withdrawal', 'purchase', 'refund', 'bonus', 'credit_purchase', 'credit_usage', 'quote_submission', 'quote_purchase')");
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropColumn(['quote_credits', 'quote_credits_before', 'quote_credits_after']);
            
            // Revert enum (remove quote types)
            DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN type ENUM('deposit', 'withdrawal', 'purchase', 'refund', 'bonus', 'credit_purchase', 'credit_usage')");
        });
    }
};
