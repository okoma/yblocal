<?php

namespace App\Console\Commands;

use App\Models\QuoteRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiredQuoteRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotes:check-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and mark expired quote requests';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for expired quote requests...');

        $expiredCount = QuoteRequest::expired()
            ->where('status', 'open')
            ->get()
            ->each(function ($request) {
                $request->markAsExpired();
            })
            ->count();

        if ($expiredCount > 0) {
            $this->info("Marked {$expiredCount} quote request(s) as expired.");
            Log::info('Expired quote requests processed', [
                'count' => $expiredCount,
                'scheduled_at' => now()->toDateTimeString(),
            ]);
        } else {
            $this->info('No expired quote requests found.');
        }

        return Command::SUCCESS;
    }
}
