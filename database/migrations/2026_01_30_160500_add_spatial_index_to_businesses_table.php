<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds an index to latitude/longitude to speed up bounding-box prefilters.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Ensure the columns exist before adding the index
            if (! Schema::hasColumn('businesses', 'latitude') || ! Schema::hasColumn('businesses', 'longitude')) {
                return;
            }

            // Composite index for simple bounding-box queries
            $table->index(['latitude', 'longitude'], 'businesses_lat_lng_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex('businesses_lat_lng_index');
        });
    }
};
