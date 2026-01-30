<?php

namespace App\Jobs;

use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Services\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 5;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $gatewaySlug,
        public array $webhookData,
        public string $eventType
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PaymentService $paymentService): void
    {
        try {
            Log::info('ProcessWebhookJob: Processing webhook', [
                'gateway' => $this->gatewaySlug,
                'event' => $this->eventType,
                'reference' => $this->webhookData['reference'] ?? null,
            ]);

            // Get the payment gateway
            $gateway = PaymentGateway::where('slug', $this->gatewaySlug)
                ->where('is_active', true)
                ->first();

            if (!$gateway) {
                Log::error('ProcessWebhookJob: Gateway not found or inactive', [
                    'gateway' => $this->gatewaySlug,
                ]);
                return;
            }

            // Process based on event type
            match ($this->eventType) {
                'charge.success', 'payment.success' => $this->handleSuccess($paymentService),
                'charge.failed', 'payment.failed' => $this->handleFailure($paymentService),
                default => Log::info('ProcessWebhookJob: Unhandled event type', [
                    'event' => $this->eventType,
                ]),
            };

            Log::info('ProcessWebhookJob: Webhook processed successfully', [
                'gateway' => $this->gatewaySlug,
                'event' => $this->eventType,
            ]);
        } catch (\Exception $e) {
            Log::error('ProcessWebhookJob: Failed to process webhook', [
                'gateway' => $this->gatewaySlug,
                'event' => $this->eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle successful payment webhook.
     */
    protected function handleSuccess(PaymentService $paymentService): void
    {
        $reference = $this->webhookData['reference'] ?? $this->webhookData['tx_ref'] ?? null;

        if (!$reference) {
            Log::warning('ProcessWebhookJob: No reference in webhook data');
            return;
        }

        $transaction = Transaction::where('transaction_ref', $reference)
            ->orWhere('payment_gateway_ref', $reference)
            ->first();

        if (!$transaction) {
            Log::warning('ProcessWebhookJob: Transaction not found', [
                'reference' => $reference,
            ]);
            return;
        }

        // Verify payment with gateway
        $verified = $paymentService->verifyPayment($transaction->id);

        if ($verified) {
            Log::info('ProcessWebhookJob: Payment verified and processed', [
                'transaction_id' => $transaction->id,
            ]);
        }
    }

    /**
     * Handle failed payment webhook.
     */
    protected function handleFailure(PaymentService $paymentService): void
    {
        $reference = $this->webhookData['reference'] ?? $this->webhookData['tx_ref'] ?? null;

        if (!$reference) {
            Log::warning('ProcessWebhookJob: No reference in failure webhook');
            return;
        }

        $transaction = Transaction::where('transaction_ref', $reference)
            ->orWhere('payment_gateway_ref', $reference)
            ->first();

        if (!$transaction) {
            Log::warning('ProcessWebhookJob: Transaction not found for failure', [
                'reference' => $reference,
            ]);
            return;
        }

        if ($transaction->status === 'pending') {
            $transaction->update([
                'status' => 'failed',
                'failed_at' => now(),
                'gateway_response' => $this->webhookData,
            ]);

            Log::info('ProcessWebhookJob: Transaction marked as failed', [
                'transaction_id' => $transaction->id,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessWebhookJob: Job failed permanently', [
            'gateway' => $this->gatewaySlug,
            'event' => $this->eventType,
            'error' => $exception->getMessage(),
        ]);
    }
}
