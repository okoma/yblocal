<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Exception;

class BackfillBusinessSummaries extends Command
{
    protected $signature = 'backfill:business-summaries
        {--period-type=daily : hourly|daily|monthly|yearly}
        {--start= : Start date (YYYY-MM-DD) or start period key}
        {--end= : End date (YYYY-MM-DD) or end period key}
        {--business-id= : Optional single business id to limit}
    ';

    protected $description = 'Backfill business_view_summaries from business_views for a period range';

    public function handle(): int
    {
        $periodType = $this->option('period-type') ?: 'daily';
        $start = $this->option('start');
        $end = $this->option('end');

        if (!$start) {
            $startDate = Carbon::yesterday();
        } else {
            $startDate = $this->parseToCarbon($start, $periodType);
        }

        if (!$end) {
            $endDate = $startDate;
        } else {
            $endDate = $this->parseToCarbon($end, $periodType);
        }

        if ($endDate->lessThan($startDate)) {
            $this->error('End date must be >= start date');
            return 2;
        }

        // Build period keys
        $periodKeys = $this->buildPeriodKeys($periodType, $startDate, $endDate);

        // Determine businesses to process
        $businessIdOption = $this->option('business-id');
        if ($businessIdOption) {
            $businessIds = [ (int) $businessIdOption ];
        } else {
            // find distinct business_ids from business_views in range
            $query = \DB::table('business_views');
            if ($periodType === 'daily') {
                $query->whereBetween('view_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            }
            // For other period types, fallback to entire table (safe) and dedupe
            $businessIds = $query->distinct()->pluck('business_id')->filter()->values()->toArray();
        }

        if (empty($businessIds)) {
            $this->warn('No businesses found to process.');
            return 0;
        }

        $this->info('Processing ' . count($businessIds) . ' businesses over ' . count($periodKeys) . ' period keys.');

        $errors = [];
        foreach ($businessIds as $bid) {
            foreach ($periodKeys as $pkey) {
                $this->line("Aggregating business {$bid} => {$periodType}:{$pkey}");
                try {
                    \App\Models\BusinessViewSummary::aggregateFor((int) $bid, $periodType, $pkey);
                } catch (Exception $e) {
                    $errors[] = "{$bid}:{$pkey} => " . $e->getMessage();
                    $this->error("Error for {$bid} {$pkey}: " . $e->getMessage());
                }
            }
        }

        if (!empty($errors)) {
            $this->error('Completed with errors:');
            foreach ($errors as $err) {
                $this->error($err);
            }
            return 3;
        }

        $this->info('Backfill complete.');
        return 0;
    }

    private function parseToCarbon(string $value, string $periodType): Carbon
    {
        return match($periodType) {
            'hourly' => Carbon::createFromFormat('Y-m-d-H', $value),
            'daily' => Carbon::createFromFormat('Y-m-d', $value),
            'monthly' => Carbon::createFromFormat('Y-m', $value),
            'yearly' => Carbon::createFromFormat('Y', $value),
            default => Carbon::createFromFormat('Y-m-d', $value),
        };
    }

    private function buildPeriodKeys(string $periodType, Carbon $start, Carbon $end): array
    {
        $keys = [];
        $cursor = $start->copy();
        switch ($periodType) {
            case 'hourly':
                while ($cursor->lessThanOrEqualTo($end)) {
                    $keys[] = $cursor->format('Y-m-d-H');
                    $cursor->addHour();
                }
                break;
            case 'monthly':
                while ($cursor->lessThanOrEqualTo($end)) {
                    $keys[] = $cursor->format('Y-m');
                    $cursor->addMonth();
                }
                break;
            case 'yearly':
                while ($cursor->lessThanOrEqualTo($end)) {
                    $keys[] = $cursor->format('Y');
                    $cursor->addYear();
                }
                break;
            case 'daily':
            default:
                while ($cursor->lessThanOrEqualTo($end)) {
                    $keys[] = $cursor->format('Y-m-d');
                    $cursor->addDay();
                }
                break;
        }
        return $keys;
    }
}
