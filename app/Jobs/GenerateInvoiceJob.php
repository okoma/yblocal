<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateInvoiceJob implements ShouldQueue
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
    public function handle(): void
    {
        try {
            Log::info('GenerateInvoiceJob: Generating invoice', [
                'transaction_id' => $this->transaction->id,
            ]);

            // Check if invoice already exists
            if ($this->transaction->invoice()->exists()) {
                Log::info('GenerateInvoiceJob: Invoice already exists');
                return;
            }

            // Generate invoice number
            $invoiceNumber = 'INV-' . str_pad($this->transaction->id, 8, '0', STR_PAD_LEFT);

            // Create invoice record
            $invoice = Invoice::create([
                'transaction_id' => $this->transaction->id,
                'user_id' => $this->transaction->user_id,
                'business_id' => $this->transaction->business_id,
                'invoice_number' => $invoiceNumber,
                'amount' => $this->transaction->amount,
                'currency' => $this->transaction->currency ?? 'NGN',
                'status' => 'paid',
                'issued_at' => now(),
                'paid_at' => $this->transaction->paid_at,
            ]);

            // Generate PDF (optional - can be generated on-demand)
            // $this->generatePDF($invoice);

            Log::info('GenerateInvoiceJob: Invoice generated successfully', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoiceNumber,
            ]);
        } catch (\Exception $e) {
            Log::error('GenerateInvoiceJob: Failed to generate invoice', [
                'transaction_id' => $this->transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate PDF for invoice (optional).
     */
    protected function generatePDF(Invoice $invoice): void
    {
        $pdf = Pdf::loadView('receipts.invoice', [
            'invoice' => $invoice,
            'transaction' => $this->transaction,
        ]);

        $filename = "invoices/{$invoice->invoice_number}.pdf";
        Storage::disk('local')->put($filename, $pdf->output());

        $invoice->update(['pdf_path' => $filename]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateInvoiceJob: Job failed permanently', [
            'transaction_id' => $this->transaction->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
