<?php

// ============================================
// database/migrations/2026_01_27_200000_add_quote_credits_to_wallets_table.php
// Add quote_credits column to wallets table (similar to ad_credits)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // Add quote credits (separate from ad credits and cash balance)
            $table->integer('quote_credits')->default(0)->after('ad_credits');
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('quote_credits');
        });
    }
};
