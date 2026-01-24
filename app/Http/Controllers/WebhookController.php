<?php

namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Services\ActivationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected ActivationService $activationService
    ) {}

    public function handle(Request $request, string $gateway)
    {
        // DEBUG: Log all incoming webhook requests
        Log::info("=== WEBHOOK RECEIVED ===", [
            'gateway' => $gateway,
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'raw_body' => $request->getContent(),
            'ip' => $request->ip(),
        ]);

        // Get gateway configuration
        $gatewayModel = PaymentGateway::where('slug', $gateway)
            ->where('is_enabled', true)
            ->first();

        if (!$gatewayModel || !$gatewayModel->secret_key) {
            Log::error("Webhook [{$gateway}]: Gateway not configured");
            return response()->json(['error' => 'Gateway not configured'], 400);
        }

        // Verify webhook signature (gateway-specific)
        $signatureValid = $this->verifyWebhookSignature($request, $gateway, $gatewayModel->secret_key);
        
        Log::info("Webhook [{$gateway}]: Signature verification", [
            'valid' => $signatureValid,
        ]);

        if (!$signatureValid) {
            Log::error("Webhook [{$gateway}]: Invalid signature");
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Parse webhook data (gateway-specific)
        $webhookData = $this->parseWebhookData($request, $gateway);
        
        Log::info("Webhook [{$gateway}]: Parsed data", [
            'webhook_data' => $webhookData,
        ]);

        if (!$webhookData || !isset($webhookData['event'])) {
            Log::error("Webhook [{$gateway}]: Invalid event data");
            return response()->json(['error' => 'Invalid event'], 400);
        }

        // Handle event based on type
        $this->handleWebhookEvent($webhookData, $gateway);

        return response()->json(['status' => 'success'], 200);
    }

    protected function verifyWebhookSignature(Request $request, string $gateway, string $secret): bool
    {
        return match ($gateway) {
            'paystack' => $this->verifyPaystackSignature($request, $secret),
            'flutterwave' => $this->verifyFlutterwaveSignature($request, $secret),
            default => false,
        };
    }

    protected function verifyPaystackSignature(Request $request, string $secret): bool
    {
        $signature = $request->header('x-paystack-signature');
        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $computedSignature = hash_hmac('sha512', $payload, $secret);
        
        return hash_equals($computedSignature, $signature);
    }

    protected function verifyFlutterwaveSignature(Request $request, string $secret): bool
    {
        $signature = $request->header('verif-hash');
        if (!$signature) {
            return false;
        }

        $payload = $request->all();
        $computedHash = hash_hmac('sha256', json_encode($payload), $secret);
        
        return hash_equals($computedHash, $signature);
    }

    protected function parseWebhookData(Request $request, string $gateway): ?array
    {
        return match ($gateway) {
            'paystack' => $this->parsePaystackWebhook($request),
            'flutterwave' => $this->parseFlutterwaveWebhook($request),
            default => null,
        };
    }

    protected function parsePaystackWebhook(Request $request): ?array
    {
        $event = json_decode($request->getContent(), true);
        
        return [
            'event' => $event['event'] ?? null,
            'data' => $event['data'] ?? [],
        ];
    }

    protected function parseFlutterwaveWebhook(Request $request): ?array
    {
        $payload = $request->all();
        
        return [
            'event' => $payload['event'] ?? null,
            'data' => $payload['data'] ?? [],
        ];
    }

    protected function handleWebhookEvent(array $webhookData, string $gateway): void
    {
        $event = $webhookData['event'];
        $data = $webhookData['data'];

        // Determine if it's success or failure
        $isSuccess = match ($gateway) {
            'paystack' => $event === 'charge.success',
            'flutterwave' => in_array($event, ['charge.completed', 'charge.succeeded']),
            default => false,
        };

        $isFailure = match ($gateway) {
            'paystack' => $event === 'charge.failed',
            'flutterwave' => $event === 'charge.failed',
            default => false,
        };

        Log::info("Webhook [{$gateway}]: Event classification", [
            'event' => $event,
            'is_success' => $isSuccess,
            'is_failure' => $isFailure,
        ]);

        if ($isSuccess) {
            $this->handleSuccessfulPayment($data, $gateway);
        } elseif ($isFailure) {
            $this->handleFailedPayment($data, $gateway);
        } else {
            Log::info("Webhook [{$gateway}]: Unhandled event", ['event' => $event]);
        }
    }

    protected function extractReference(array $data, string $gateway): ?string
    {
        return match ($gateway) {
            'paystack' => $data['reference'] ?? null,
            'flutterwave' => $data['tx_ref'] ?? $data['flw_ref'] ?? null,
            default => null,
        };
    }

    protected function handleSuccessfulPayment(array $data, string $gateway): void
    {
        $reference = $this->extractReference($data, $gateway);
        
        if (!$reference) {
            Log::error("Webhook [{$gateway}]: Missing reference in successful payment");
            return;
        }

        // Find transaction by reference
        $transaction = Transaction::where(function($query) use ($reference) {
                $query->where('transaction_ref', $reference)
                      ->orWhere('payment_gateway_ref', $reference);
            })
            ->where('payment_method', $gateway)
            ->where('status', 'pending')
            ->first();

        if (!$transaction) {
            Log::warning("Webhook [{$gateway}]: Transaction not found", ['reference' => $reference]);
            return;
        }

        Log::info("Webhook [{$gateway}]: Transaction found, updating", [
            'transaction_id' => $transaction->id,
            'reference' => $reference,
        ]);

        // Update transaction
        $transaction->update([
            'payment_gateway_ref' => $reference,
            'gateway_response' => $data,
        ]);

        $this->activationService->completeAndActivate($transaction);

        Log::info("Webhook [{$gateway}]: Payment processed successfully", [
            'reference' => $reference,
            'transaction_id' => $transaction->id,
        ]);
    }

    protected function handleFailedPayment(array $data, string $gateway): void
    {
        $reference = $this->extractReference($data, $gateway);
        
        if (!$reference) {
            return;
        }

        $transaction = Transaction::where(function($query) use ($reference) {
                $query->where('transaction_ref', $reference)
                      ->orWhere('payment_gateway_ref', $reference);
            })
            ->where('payment_method', $gateway)
            ->where('status', 'pending')
            ->first();

        if ($transaction) {
            if (!$transaction->payment_gateway_ref) {
                $transaction->update(['payment_gateway_ref' => $reference]);
            }
            
            $transaction->markAsFailed();
            $transaction->update(['gateway_response' => $data]);

            Log::info("Webhook [{$gateway}]: Payment failed", [
                'reference' => $reference,
                'transaction_id' => $transaction->id,
            ]);
        }
    }
}