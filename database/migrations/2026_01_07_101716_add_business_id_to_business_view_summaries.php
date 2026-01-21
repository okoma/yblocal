<?php
// database/migrations/2024_01_XX_000004_add_business_id_to_business_view_summaries.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_view_summaries', function (Blueprint $table) {
            // Add business_id column
            $table->unsignedBigInteger('business_id')->nullable()->after('id');
            
            // Add foreign key with explicit name
            $table->foreign('business_id', 'bvs_business_id_fk')
                  ->references('id')
                  ->on('businesses')
                  ->onDelete('cascade');
            
            // Add index with explicit name
            $table->index('business_id', 'bvs_business_id_idx');
        });
        
        // Drop existing index if it exists
        DB::statement('ALTER TABLE business_view_summaries DROP INDEX IF EXISTS business_view_summaries_business_branch_id_period_type_period_key_index');
        
        // Add composite indexes with explicit short names
        Schema::table('business_view_summaries', function (Blueprint $table) {
            $table->index(['business_id', 'period_type', 'period_key'], 'bvs_bus_period_idx');
            $table->index(['business_branch_id', 'period_type', 'period_key'], 'bvs_branch_period_idx');
        });
        
        // Make business_branch_id nullable
        DB::statement('ALTER TABLE business_view_summaries MODIFY business_branch_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        Schema::table('business_view_summaries', function (Blueprint $table) {
            // Drop indexes with explicit names
            $table->dropIndex('bvs_branch_period_idx');
            $table->dropIndex('bvs_bus_period_idx');
            $table->dropIndex('bvs_business_id_idx');
            
            // Drop foreign key
            $table->dropForeign('bvs_business_id_fk');
            
            // Drop column
            $table->dropColumn('business_id');
        });
        
        // Restore business_branch_id to NOT NULL
        DB::statement('UPDATE business_view_summaries SET business_branch_id = 0 WHERE business_branch_id IS NULL');
        DB::statement('ALTER TABLE business_view_summaries MODIFY business_branch_id BIGINT UNSIGNED NOT NULL');
    }
};