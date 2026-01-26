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
            $table->foreignId('business_id')->nullable()->constrained('businesses')->onDelete('cascade');
            $table->foreignId('business_branch_id')->nullable()->constrained('business_branches')->onDelete('cascade');
            
            // Page Type (where the impression occurred)
            $table->enum('page_type', [
                'archive',      // Archive/listing page
                'category',     // Category page
                'search',       // Search results page
                'related',      // Related businesses section
                'featured',     // Featured listings section
                'other'         // Other pages
            ])->default('archive');
            
            // Referral Source (where the user came from)
            $table->enum('referral_source', [
                'yellowbooks',  // Internal YellowBooks navigation
                'google',       // Google Search
                'bing',         // Bing Search
                'facebook',     // Facebook
                'instagram',    // Instagram
                'twitter',      // Twitter/X
                'linkedin',     // LinkedIn
                'direct',       // Direct URL visit
                'other'         // Other sources
            ])->default('direct');
            
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
