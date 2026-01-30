<?php

namespace App\Jobs;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireQuoteRequestsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('ExpireQuoteRequestsJob: Checking for expired quote requests');

            $expiredCount = QuoteRequest::where('status', 'open')
                ->where('expires_at', '<', now())
                ->update([
                    'status' => 'expired',
                ]);

            Log::info('ExpireQuoteRequestsJob: Expired quote requests', [
                'count' => $expiredCount,
            ]);

            // Optionally notify users about expired quote requests
            if ($expiredCount > 0) {
                $expiredRequests = QuoteRequest::where('status', 'expired')
                    ->where('expires_at', '>=', now()->subMinutes(5))
                    ->with('user')
                    ->get();

                foreach ($expiredRequests as $request) {
                    // Dispatch notification job
                    // SendNotificationJob::dispatch(
                    //     $request->user,
                    //     new QuoteRequestExpiredNotification($request)
                    // );
                }
            }
        } catch (\Exception $e) {
            Log::error('ExpireQuoteRequestsJob: Failed to expire quote requests', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
