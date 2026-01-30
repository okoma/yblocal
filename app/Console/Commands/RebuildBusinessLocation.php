<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Business;
use Illuminate\Support\Facades\DB;

class RebuildBusinessLocation extends Command
{
    protected $signature = 'businesses:rebuild-location {--chunk=500} {--dry-run}';
    protected $description = 'Rebuild MySQL POINT `location` column for businesses from latitude/longitude in batches';

    public function handle()
    {
        $chunk = (int) $this->option('chunk');
        $dryRun = (bool) $this->option('dry-run');

        $driver = DB::getDriverName();
        if ($driver !== 'mysql') {
            $this->error('This command is intended for MySQL only. Current driver: ' . $driver);
            return 1;
        }

        $this->info("Starting rebuild of businesses.location in chunks of {$chunk}");

        $total = Business::whereNotNull('latitude')->whereNotNull('longitude')->count();
        $this->info("Total businesses to process: {$total}");

        $processed = 0;

        Business::whereNotNull('latitude')->whereNotNull('longitude')
            ->chunkById($chunk, function ($items) use (&$processed, $dryRun) {
                foreach ($items as $b) {
                    $processed++;
                    $point = "POINT({$b->longitude} {$b->latitude})";
                    if ($dryRun) {
                        $this->line("Dry: would update id={$b->id} => {$point}");
                        continue;
                    }

                    try {
                        DB::statement('UPDATE `businesses` SET `location` = ST_GeomFromText(?) WHERE id = ?', [$point, $b->id]);
                    } catch (\Throwable $e) {
                        $this->error("Failed id={$b->id}: " . $e->getMessage());
                    }
                }
                $this->info("Processed {$processed} so far...");
            });

        $this->info("Done. Processed {$processed} records.");
        return 0;
    }
}
