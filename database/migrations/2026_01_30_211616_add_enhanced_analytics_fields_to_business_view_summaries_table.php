<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('business_view_summaries', function (Blueprint $table) {
            // Geographic data for views
            $table->json('views_by_city')->nullable()->after('views_by_country');
            
            // Geographic and device data for impressions
            $table->json('impressions_by_country')->nullable()->after('impressions_by_page_type');
            $table->json('impressions_by_city')->nullable()->after('impressions_by_country');
            $table->json('impressions_by_device')->nullable()->after('impressions_by_city');
            
            // Geographic and device data for clicks
            $table->json('clicks_by_country')->nullable()->after('clicks_by_page_type');
            $table->json('clicks_by_city')->nullable()->after('clicks_by_country');
            $table->json('clicks_by_device')->nullable()->after('clicks_by_city');
            $table->unsignedBigInteger('unique_visitors')->default(0)->after('clicks_by_device');
            
            // Interaction breakdowns
            $table->json('interactions_by_source')->nullable()->after('total_map_clicks');
            $table->json('interactions_by_device')->nullable()->after('interactions_by_source');
            
            // Lead data
            $table->unsignedBigInteger('total_leads')->default(0)->after('interactions_by_device');
            $table->json('leads_by_status')->nullable()->after('total_leads');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_view_summaries', function (Blueprint $table) {
            $table->dropColumn([
                'views_by_city',
                'impressions_by_country',
                'impressions_by_city',
                'impressions_by_device',
                'clicks_by_country',
                'clicks_by_city',
                'clicks_by_device',
                'unique_visitors',
                'interactions_by_source',
                'interactions_by_device',
                'total_leads',
                'leads_by_status',
            ]);
        });
    }
};
