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
            // Step 1: Drop the foreign key constraint first (required before dropping unique)
            $table->dropForeign(['user_id']);
        });
        
        Schema::table('wallets', function (Blueprint $table) {
            // Step 2: Drop unique constraint by name (Laravel convention: {table}_{column}_unique)
            $table->dropUnique('wallets_user_id_unique');
        });
        
        Schema::table('wallets', function (Blueprint $table) {
            // Step 3: Re-add the foreign key constraint (without unique)
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Reverse the process: drop foreign key, add unique, re-add foreign key with unique
        Schema::table('wallets', function (Blueprint $table) {
            // Step 1: Drop the foreign key
            $table->dropForeign(['user_id']);
        });
        
        Schema::table('wallets', function (Blueprint $table) {
            // Step 2: Add unique constraint back
            $table->unique('user_id');
        });
        
        Schema::table('wallets', function (Blueprint $table) {
            // Step 3: Re-add foreign key (note: this won't be unique anymore in Laravel's way,
            // but the unique constraint above handles uniqueness)
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
