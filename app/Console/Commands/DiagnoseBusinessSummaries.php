<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Exception;

class DiagnoseBusinessSummaries extends Command
{
    protected $signature = 'diagnose:business-summaries
        {business_id : Business ID to inspect}
        {--period-type=daily : hourly|daily|monthly|yearly}
        {--period-key= : Period key (defaults to yesterday for daily)}
        {--run-aggregate : Run aggregateFor() after diagnostics}
    ';

    protected $description = 'Diagnose business_views and business_view_summaries for a business and period';

    public function handle(): int
    {
        $businessId = (int) $this->argument('business_id');
        $periodType = $this->option('period-type');
        $periodKey = $this->option('period-key') ?: $this->defaultPeriodKey($periodType);

        $this->info("Diagnosing business {$businessId} for {$periodType} => {$periodKey}");

        // Counts in business_views
        $viewsCount = \DB::table('business_views')->where('business_id', $businessId)
            ->when($periodType === 'daily', fn($q) => $q->where('view_date', $periodKey))
            ->when($periodType === 'hourly', fn($q) => $q->where('view_date', substr($periodKey,0,10))->where('view_hour', substr($periodKey,-2)))
            ->count();

        $this->line("business_views rows for business_id={$businessId}: {$viewsCount}");

        // Show sample rows
        $samples = \DB::table('business_views')->where('business_id', $businessId)->limit(5)->get();
        if ($samples->isEmpty()) {
            $this->warn('No sample rows found in business_views for this business.');
        } else {
            $this->line('Sample business_views rows:');
            $this->table(array_keys((array) $samples->first()), $samples->map(fn($r) => (array) $r)->toArray());
        }

        // Check business_view_summaries existing
        $summary = \DB::table('business_view_summaries')
            ->where('business_id', $businessId)
            ->where('period_type', $periodType)
            ->where('period_key', $periodKey)
            ->first();

        if ($summary) {
            $this->info('Existing summary found:');
            $this->line(json_encode($summary));
        } else {
            $this->warn('No existing summary found for that period.');
        }

        if ($this->option('run-aggregate')) {
            $this->info('Attempting to run aggregation now...');
            try {
                $res = \App\Models\BusinessViewSummary::aggregateFor($businessId, $periodType, $periodKey);
                $this->info('aggregateFor() completed. Summary id: ' . $res->id);
            } catch (Exception $e) {
                $this->error('aggregateFor() threw exception: ' . $e->getMessage());
                $this->error($e->getTraceAsString());
                return 2;
            }

            $newSummary = \DB::table('business_view_summaries')
                ->where('business_id', $businessId)
                ->where('period_type', $periodType)
                ->where('period_key', $periodKey)
                ->first();

            if ($newSummary) {
                $this->info('New/updated summary:');
                $this->line(json_encode($newSummary));
            } else {
                $this->warn('After aggregateFor(), no summary row exists — check logs and DB constraints.');
            }
        }

        $this->info('Diagnosis complete.');
        return 0;
    }

    private function defaultPeriodKey(string $periodType): string
    {
        $t = Carbon::yesterday();
        return match($periodType) {
            'hourly' => $t->format('Y-m-d-H'),
            'daily' => $t->format('Y-m-d'),
            'monthly' => $t->format('Y-m'),
            'yearly' => $t->format('Y'),
            default => $t->format('Y-m-d')
        };
    }
}
