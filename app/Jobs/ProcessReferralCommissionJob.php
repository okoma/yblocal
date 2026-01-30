<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\ReferralCommissionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessReferralCommissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Transaction $transaction
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ReferralCommissionService $commissionService): void
    {
        try {
            Log::info('ProcessReferralCommissionJob: Processing commission', [
                'transaction_id' => $this->transaction->id,
                'transaction_ref' => $this->transaction->transaction_ref,
            ]);

            // Process customer referral commission (10% of payment)
            $commissionService->processCustomerCommission($this->transaction);

            Log::info('ProcessReferralCommissionJob: Commission processed successfully', [
                'transaction_id' => $this->transaction->id,
            ]);
        } catch (\Exception $e) {
            Log::error('ProcessReferralCommissionJob: Failed to process commission', [
                'transaction_id' => $this->transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessReferralCommissionJob: Job failed permanently', [
            'transaction_id' => $this->transaction->id,
            'error' => $exception->getMessage(),
        ]);

        // Optionally notify admin of failed commission processing
        // \Notification::route('mail', config('mail.admin_email'))
        //     ->notify(new CommissionProcessingFailedNotification($this->transaction, $exception));
    }
}
