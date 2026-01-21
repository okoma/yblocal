<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_views', function (Blueprint $table) {
            // Add business_id column (nullable from the start)
            $table->unsignedBigInteger('business_id')->nullable()->after('id');
            
            // Add foreign key with explicit name
            $table->foreign('business_id', 'bv_business_id_foreign')
                  ->references('id')
                  ->on('businesses')
                  ->onDelete('cascade');
            
            // Add single column index with explicit name
            $table->index('business_id', 'bv_business_id_index');
            
            // Add composite indexes with explicit names
            $table->index(['business_id', 'view_date'], 'bv_business_date_index');
            $table->index(['business_branch_id', 'view_date'], 'bv_branch_date_index');
        });
        
        // Make business_branch_id nullable using raw SQL
        DB::statement('ALTER TABLE business_views MODIFY business_branch_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        Schema::table('business_views', function (Blueprint $table) {
            // Drop indexes first (with explicit names)
            $table->dropIndex('bv_branch_date_index');
            $table->dropIndex('bv_business_date_index');
            $table->dropIndex('bv_business_id_index');
            
            // Drop foreign key
            $table->dropForeign('bv_business_id_foreign');
            
            // Drop column
            $table->dropColumn('business_id');
        });
        
        // Restore business_branch_id to NOT NULL
        // First, set a default value for any NULL records
        DB::statement('UPDATE business_views SET business_branch_id = 0 WHERE business_branch_id IS NULL');
        DB::statement('ALTER TABLE business_views MODIFY business_branch_id BIGINT UNSIGNED NOT NULL');
    }
};