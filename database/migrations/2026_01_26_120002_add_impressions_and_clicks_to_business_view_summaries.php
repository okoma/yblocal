<?php

// ============================================
// database/migrations/2026_01_26_120002_add_impressions_and_clicks_to_business_view_summaries.php
// Add impressions and clicks columns to business_view_summaries table
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_view_summaries', function (Blueprint $table) {
            // Add impressions stats
            $table->integer('total_impressions')->default(0)->after('total_views');
            $table->json('impressions_by_source')->nullable()->after('views_by_source');
            $table->json('impressions_by_page_type')->nullable()->after('impressions_by_source');
            
            // Add clicks stats
            $table->integer('total_clicks')->default(0)->after('total_impressions');
            $table->json('clicks_by_source')->nullable()->after('total_clicks');
            $table->json('clicks_by_page_type')->nullable()->after('clicks_by_source');
        });
    }

    public function down(): void
    {
        Schema::table('business_view_summaries', function (Blueprint $table) {
            $table->dropColumn([
                'total_impressions',
                'impressions_by_source',
                'impressions_by_page_type',
                'total_clicks',
                'clicks_by_source',
                'clicks_by_page_type',
            ]);
        });
    }
};
