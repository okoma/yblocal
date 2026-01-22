<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    /**
     * Handle Paystack payment callback (user redirect)
     */
    public function paystackCallback(Request $request)
    {
        $reference = $request->query('reference');
        
        if (!$reference) {
            return redirect()->route('filament.business.pages.subscription')
                ->with('error', 'Invalid payment reference.');
        }

        // Find transaction
        $transaction = Transaction::where(function($query) use ($reference) {
                $query->where('transaction_ref', $reference)
                      ->orWhere('payment_gateway_ref', $reference);
            })
            ->where('payment_method', 'paystack')
            ->first();

        if (!$transaction) {
            return redirect()->route('filament.business.pages.subscription')
                ->with('error', 'Transaction not found.');
        }

        // Verify payment with Paystack
        $gateway = PaymentGateway::where('slug', 'paystack')
            ->where('is_enabled', true)
            ->first();

        if (!$gateway || !$gateway->secret_key) {
            Log::error('Paystack callback: Gateway not configured');
            return redirect()->route('filament.business.pages.subscription')
                ->with('error', 'Payment gateway not configured.');
        }

        // Verify transaction with Paystack API
        $verified = $this->verifyPaystackTransaction($reference, $gateway->secret_key);

        if ($verified && $verified['status']) {
            // Payment successful
            if ($transaction->status === 'pending') {
                $transaction->update([
                    'payment_gateway_ref' => $reference,
                    'status' => 'completed',
                    'paid_at' => now(),
                    'gateway_response' => $verified,
                ]);

                // Handle transactionable (subscription, wallet, campaign)
                $this->handleSuccessfulTransaction($transaction);
            }

            return redirect()->route('filament.business.pages.subscription')
                ->with('success', 'Payment successful! Your subscription has been activated.');
        } else {
            // Payment failed
            if ($transaction->status === 'pending') {
                $transaction->markAsFailed();
                $transaction->update([
                    'payment_gateway_ref' => $reference,
                    'gateway_response' => $verified ?? ['error' => 'Verification failed'],
                ]);
            }

            return redirect()->route('filament.business.pages.subscription')
                ->with('error', 'Payment failed. Please try again.');
        }
    }

    /**
     * Handle Flutterwave payment callback (user redirect)
     */
    public function flutterwaveCallback(Request $request)
    {
        $txRef = $request->query('tx_ref') ?? $request->query('transaction_id');
        $status = $request->query('status');

        if (!$txRef) {
            return redirect()->route('filament.business.pages.subscription')
                ->with('error', 'Invalid payment reference.');
        }

        // Find transaction
        $transaction = Transaction::where(function($query) use ($txRef) {
                $query->where('transaction_ref', $txRef)
                      ->orWhere('payment_gateway_ref', $txRef);
            })
            ->where('payment_method', 'flutterwave')
            ->first();

        if (!$transaction) {
            return redirect()->route('filament.business.pages.subscription')
                ->with('error', 'Transaction not found.');
        }

        // Verify payment with Flutterwave
        $gateway = PaymentGateway::where('slug', 'flutterwave')
            ->where('is_enabled', true)
            ->first();

        if (!$gateway || !$gateway->secret_key) {
            Log::error('Flutterwave callback: Gateway not configured');
            return redirect()->route('filament.business.pages.subscription')
                ->with('error', 'Payment gateway not configured.');
        }

        // Verify transaction with Flutterwave API
        $verified = $this->verifyFlutterwaveTransaction($txRef, $gateway->secret_key);

        if ($verified && ($verified['status'] === 'successful' || $status === 'successful')) {
            // Payment successful
            if ($transaction->status === 'pending') {
                $transaction->update([
                    'payment_gateway_ref' => $txRef,
                    'status' => 'completed',
                    'paid_at' => now(),
                    'gateway_response' => $verified,
                ]);

                // Handle transactionable (subscription, wallet, campaign)
                $this->handleSuccessfulTransaction($transaction);
            }

            return redirect()->route('filament.business.pages.subscription')
                ->with('success', 'Payment successful! Your subscription has been activated.');
        } else {
            // Payment failed
            if ($transaction->status === 'pending') {
                $transaction->markAsFailed();
                $transaction->update([
                    'payment_gateway_ref' => $txRef,
                    'gateway_response' => $verified ?? ['error' => 'Verification failed', 'status' => $status],
                ]);
            }

            return redirect()->route('filament.business.pages.subscription')
                ->with('error', 'Payment failed. Please try again.');
        }
    }

    /**
     * Verify Paystack transaction
     */
    protected function verifyPaystackTransaction(string $reference, string $secretKey): ?array
    {
        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . $reference,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer " . $secretKey,
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                Log::error('Paystack verification error', ['error' => $err]);
                return null;
            }

            $result = json_decode($response, true);
            
            if ($result && $result['status'] && $result['data']['status'] === 'success') {
                return $result['data'];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Paystack verification exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verify Flutterwave transaction
     */
    protected function verifyFlutterwaveTransaction(string $txRef, string $secretKey): ?array
    {
        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/" . $txRef . "/verify",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer " . $secretKey,
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                Log::error('Flutterwave verification error', ['error' => $err]);
                return null;
            }

            $result = json_decode($response, true);
            
            if ($result && $result['status'] === 'success' && $result['data']['status'] === 'successful') {
                return $result['data'];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Flutterwave verification exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Handle successful transaction (activate subscription, fund wallet, activate campaign)
     */
    protected function handleSuccessfulTransaction(Transaction $transaction)
    {
        if (!$transaction->transactionable_type || !$transaction->transactionable_id) {
            return;
        }

        $transactionableType = $transaction->transactionable_type;
        
        if ($transactionableType === \App\Models\Subscription::class || $transactionableType === 'App\\Models\\Subscription') {
            $subscription = \App\Models\Subscription::find($transaction->transactionable_id);
            if ($subscription && $subscription->status === 'pending') {
                $subscription->update([
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addMonths($subscription->ends_at->diffInMonths($subscription->starts_at) ?: 1),
                ]);
            }
        } elseif ($transactionableType === \App\Models\Wallet::class || $transactionableType === 'App\\Models\\Wallet') {
            $wallet = \App\Models\Wallet::find($transaction->transactionable_id);
            if ($wallet) {
                $wallet->deposit(
                    $transaction->amount,
                    'Wallet funding via payment gateway',
                    $transaction
                );
            }
        } elseif ($transactionableType === \App\Models\AdCampaign::class || $transactionableType === 'App\\Models\\AdCampaign') {
            $campaign = \App\Models\AdCampaign::find($transaction->transactionable_id);
            if ($campaign) {
                $campaign->update([
                    'is_paid' => true,
                    'is_active' => true,
                    'transaction_id' => $transaction->id,
                ]);
            }
        }
    }
}
