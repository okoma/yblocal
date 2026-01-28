<?php

// ============================================
// database/migrations/2026_01_27_100000_add_business_id_to_wallets_table.php
// Add business_id to wallets table (nullable initially for backfill)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // Add business_id as nullable first (will be backfilled, then made required)
            $table->unsignedBigInteger('business_id')->nullable()->after('user_id');
            
            // Add index for faster queries
            $table->index('business_id');
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropIndex(['business_id']);
            $table->dropColumn('business_id');
        });
    }
};
