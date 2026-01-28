<?php
// ============================================
// database/migrations/2026_01_27_000003_change_referral_source_to_string_in_business_interactions_table.php
// Change referral_source from ENUM to STRING
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change referral_source from ENUM to VARCHAR(20)
        DB::statement("ALTER TABLE business_interactions MODIFY referral_source VARCHAR(20) NOT NULL DEFAULT 'direct'");
    }

    public function down(): void
    {
        // Revert back to ENUM with original values
        DB::statement("ALTER TABLE business_interactions MODIFY referral_source ENUM('yellowbooks', 'google', 'bing', 'facebook', 'instagram', 'twitter', 'linkedin', 'direct', 'other') NOT NULL DEFAULT 'direct'");
    }
};