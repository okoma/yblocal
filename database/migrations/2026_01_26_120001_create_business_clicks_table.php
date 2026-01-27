<?php
// ============================================
// database/migrations/2026_01_26_120001_create_business_clicks_table.php
// Track clicks to business detail pages (cookie-based, one per person)
// ============================================
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained('businesses')->onDelete('cascade');
            $table->foreignId('business_branch_id')->nullable()->constrained('business_branches')->onDelete('cascade');
            
            // Cookie ID to prevent duplicate clicks from same person
            $table->string('cookie_id', 64)->index(); // This already creates the index
            
            // Referral Source (where the click came from)
            $table->string('referral_source')->default('direct');
            
            // Page Type (where the click originated from, if from YellowBooks)
            $table->string('source_page_type')->nullable();
            
            // Visitor Location (IP-based)
            $table->string('country')->default('Unknown');
            $table->string('country_code', 2)->nullable(); // NG, US, UK
            $table->string('region')->default('Unknown');
            $table->string('city')->default('Unknown');
            $table->string('ip_address', 45)->nullable(); // IPv4 or IPv6
            
            // User Agent
            $table->text('user_agent')->nullable();
            $table->string('device_type')->nullable(); // mobile, desktop, tablet
            
            // Timestamps for hourly tracking
            $table->timestamp('clicked_at')->useCurrent();
            $table->date('click_date'); // For daily grouping
            $table->string('click_hour', 2); // 00-23 for hourly stats
            $table->string('click_month', 7); // YYYY-MM for monthly stats
            $table->string('click_year', 4); // YYYY for yearly stats
            
            $table->timestamps();
            
            // Unique constraint: one click per business per cookie_id (prevents duplicates)
            $table->unique(['business_id', 'cookie_id'], 'business_cookie_unique');
            
            // Indexes for fast queries
            $table->index(['business_id', 'click_date']);
            $table->index(['business_id', 'click_month']);
            $table->index(['business_id', 'referral_source']);
            $table->index(['referral_source', 'click_date']);
            // REMOVED: $table->index('cookie_id'); // Already indexed above on line 16
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_clicks');
    }
};