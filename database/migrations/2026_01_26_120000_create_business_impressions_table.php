<?php
// ============================================
// database/migrations/2026_01_26_120000_create_business_impressions_table.php
// Track impressions when business listings are visible on archive/category/search pages
// ============================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_impressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            
            // Page Type (where the impression occurred)
            $table->string('page_type', 20)->default('archive')->index();
            
            // Referral Source (where the user came from)
            $table->string('referral_source', 20)->default('direct')->index();
            
            // Visitor Location (IP-based)
            $table->string('country')->default('Unknown');
            $table->string('country_code', 2)->nullable(); // NG, US, UK
            $table->string('region')->default('Unknown');
            $table->string('city')->default('Unknown');
            $table->string('ip_address', 45)->nullable(); // IPv4 or IPv6
            
            // User Agent
            $table->text('user_agent')->nullable();
            $table->string('device_type', 20)->nullable(); // mobile, desktop, tablet
            
            // Timestamps for hourly tracking
            $table->timestamp('impressed_at')->useCurrent();
            $table->date('impression_date'); // For daily grouping
            $table->string('impression_hour', 2); // 00-23 for hourly stats
            $table->string('impression_month', 7); // YYYY-MM for monthly stats
            $table->string('impression_year', 4); // YYYY for yearly stats
            
            $table->timestamps();
            
            // Indexes for fast queries
            $table->index(['business_id', 'impression_date']);
            $table->index(['business_id', 'impression_month']);
            $table->index(['business_id', 'referral_source']);
            $table->index(['business_id', 'page_type']);
            $table->index(['referral_source', 'impression_date']);
            $table->index(['page_type', 'impression_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_impressions');
    }
};