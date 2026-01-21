<?php
// database/migrations/2024_01_XX_000002_add_business_id_to_business_interactions.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_interactions', function (Blueprint $table) {
            // Add business_id column
            $table->unsignedBigInteger('business_id')->nullable()->after('id');
            
            // Add foreign key with explicit name
            $table->foreign('business_id', 'bi_business_id_fk')
                  ->references('id')
                  ->on('businesses')
                  ->onDelete('cascade');
            
            // Add index with explicit name
            $table->index('business_id', 'bi_business_id_idx');
        });
        
        // Drop existing index if it exists
        DB::statement('ALTER TABLE business_interactions DROP INDEX IF EXISTS business_interactions_business_branch_id_interaction_date_index');
        
        // Add composite indexes with explicit short names
        Schema::table('business_interactions', function (Blueprint $table) {
            $table->index(['business_id', 'interaction_date'], 'bi_bus_date_idx');
            $table->index(['business_branch_id', 'interaction_date'], 'bi_branch_date_idx');
            $table->index(['business_id', 'interaction_type'], 'bi_bus_type_idx');
            $table->index(['business_branch_id', 'interaction_type'], 'bi_branch_type_idx');
        });
        
        // Make business_branch_id nullable
        DB::statement('ALTER TABLE business_interactions MODIFY business_branch_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        Schema::table('business_interactions', function (Blueprint $table) {
            // Drop indexes with explicit names
            $table->dropIndex('bi_branch_type_idx');
            $table->dropIndex('bi_bus_type_idx');
            $table->dropIndex('bi_branch_date_idx');
            $table->dropIndex('bi_bus_date_idx');
            $table->dropIndex('bi_business_id_idx');
            
            // Drop foreign key
            $table->dropForeign('bi_business_id_fk');
            
            // Drop column
            $table->dropColumn('business_id');
        });
        
        // Restore business_branch_id to NOT NULL
        DB::statement('UPDATE business_interactions SET business_branch_id = 0 WHERE business_branch_id IS NULL');
        DB::statement('ALTER TABLE business_interactions MODIFY business_branch_id BIGINT UNSIGNED NOT NULL');
    }
};