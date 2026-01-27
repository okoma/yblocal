<?php

// ============================================
// database/migrations/2026_01_27_100004_make_business_id_required_and_add_foreign_keys.php
// Make business_id required and add foreign key constraints
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First, delete any records that still have NULL business_id (shouldn't happen after backfill)
        DB::table('wallets')->whereNull('business_id')->delete();
        DB::table('wallet_transactions')->whereNull('business_id')->delete();
        DB::table('transactions')->whereNull('business_id')->delete();

        // Make business_id required and add foreign keys for wallets
        Schema::table('wallets', function (Blueprint $table) {
            // Drop existing index
            $table->dropIndex(['business_id']);
            
            // Make business_id required
            $table->unsignedBigInteger('business_id')->nullable(false)->change();
            
            // Add foreign key with cascade delete
            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->onDelete('cascade');
            
            // Add unique index on business_id (one wallet per business)
            $table->unique('business_id');
        });

        // Make business_id required and add foreign keys for wallet_transactions
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // Drop existing index
            $table->dropIndex(['business_id']);
            
            // Make business_id required
            $table->unsignedBigInteger('business_id')->nullable(false)->change();
            
            // Add foreign key with cascade delete
            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->onDelete('cascade');
            
            // Re-add index for faster queries
            $table->index('business_id');
        });

        // Make business_id required and add foreign keys for transactions
        Schema::table('transactions', function (Blueprint $table) {
            // Drop existing index
            $table->dropIndex(['business_id']);
            
            // Make business_id required
            $table->unsignedBigInteger('business_id')->nullable(false)->change();
            
            // Add foreign key with cascade delete
            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->onDelete('cascade');
            
            // Re-add index for faster queries
            $table->index('business_id');
        });
    }

    public function down(): void
    {
        // Remove foreign keys and make business_id nullable again
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropUnique(['business_id']);
            $table->unsignedBigInteger('business_id')->nullable()->change();
            $table->index('business_id');
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropIndex(['business_id']);
            $table->unsignedBigInteger('business_id')->nullable()->change();
            $table->index('business_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropIndex(['business_id']);
            $table->unsignedBigInteger('business_id')->nullable()->change();
            $table->index('business_id');
        });
    }
};
