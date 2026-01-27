<?php

// ============================================
// database/migrations/2026_01_27_100005_remove_user_id_unique_from_wallets_table.php
// Remove unique constraint on user_id since wallets are now business-scoped
// Each business has one wallet, but a user can have multiple wallets (one per business)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the unique constraint on user_id
        // Wallets are now business-scoped, so multiple wallets can share the same user_id
        // Keep user_id as a regular foreign key (for audit purposes)
        
        Schema::table('wallets', function (Blueprint $table) {
            // Drop unique constraint by name (Laravel convention: {table}_{column}_unique)
            $table->dropUnique('wallets_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // Restore unique constraint on user_id (for rollback)
            $table->unique('user_id');
        });
    }
};
