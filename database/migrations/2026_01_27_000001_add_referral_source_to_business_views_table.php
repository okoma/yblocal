<?php
// ============================================
// database/migrations/2026_01_27_000001_add_referral_source_to_business_views_table.php
// Add referral_source column and make business_id NOT NULL
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_views', function (Blueprint $table) {
            // Add referral_source as string
            $table->string('referral_source', 20)->default('direct')->after('business_id');
            
            // Add index for referral_source
            $table->index('referral_source');
            $table->index(['business_id', 'referral_source']);
        });
        
        // Make business_id NOT NULL (using raw SQL to modify existing column)
        DB::statement('ALTER TABLE business_views MODIFY business_id BIGINT UNSIGNED NOT NULL');
    }

    public function down(): void
    {
        Schema::table('business_views', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['business_id', 'referral_source']);
            $table->dropIndex(['referral_source']);
            
            // Drop column
            $table->dropColumn('referral_source');
        });
        
        // Revert business_id to nullable
        DB::statement('ALTER TABLE business_views MODIFY business_id BIGINT UNSIGNED NULL');
    }
};

