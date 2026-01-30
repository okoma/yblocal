<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only run on MySQL connections
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Add POINT column and spatial index, then backfill from latitude/longitude
        // Use raw statements because Schema may not expose spatial methods on all platforms
        if (! Schema::hasColumn('businesses', 'location')) {
            DB::statement('ALTER TABLE `businesses` ADD COLUMN `location` POINT NULL AFTER `longitude`');
        }

        // Create spatial index if not exists
        // MySQL requires SPATIAL INDEX on MyISAM or InnoDB (MySQL 5.7+ supports InnoDB spatial indexes)
        try {
            DB::statement('CREATE SPATIAL INDEX IF NOT EXISTS `businesses_location_spatial_index` ON `businesses` (`location`)');
        } catch (\Throwable $e) {
            // Some MySQL versions don't support IF NOT EXISTS for spatial index; attempt check then create
            try {
                DB::statement('CREATE SPATIAL INDEX `businesses_location_spatial_index` ON `businesses` (`location`)');
            } catch (\Throwable $inner) {
                // ignore; index creation may fail in older DB engines
                logger()->warning('Could not create spatial index for businesses.location: ' . $inner->getMessage());
            }
        }

        // Backfill existing rows where lat/lng present
        DB::statement("
            UPDATE `businesses`
            SET `location` = ST_GeomFromText(CONCAT('POINT(', `longitude`, ' ', `latitude`, ')'))
            WHERE `latitude` IS NOT NULL AND `longitude` IS NOT NULL
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Drop spatial index if exists, then drop column
        try {
            DB::statement('DROP INDEX `businesses_location_spatial_index` ON `businesses`');
        } catch (\Throwable $e) {
            // ignore
        }

        if (Schema::hasColumn('businesses', 'location')) {
            DB::statement('ALTER TABLE `businesses` DROP COLUMN `location`');
        }
    }
};
