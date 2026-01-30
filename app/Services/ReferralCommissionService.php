<?php

namespace App\Services;

use App\Services\ReferralConfig;
use App\Models\Transaction;
use App\Models\CustomerReferral;
use App\Models\CustomerReferralTransaction;
use Illuminate\Support\Facades\Log;

/**
 * Processes 10% commission for customers who referred a business
 * when that business makes a payment (subscription, ad credits, quote credits, wallet funding).
 */
class ReferralCommissionService
{
    /**
     * Process customer referral commission after a business payment is confirmed.
     * Idempotent: skips if commission for this transaction was already paid.
     */
    public function processCustomerCommission(Transaction $transaction): void
    {
        $transaction->refresh();

        $businessId = $this->getBusinessIdFromTransaction($transaction);
        if (!$businessId) {
            return;
        }

        $customerReferral = CustomerReferral::where('referred_business_id', $businessId)->first();
        if (!$customerReferral) {
            return;
        }

        // Idempotency: already paid commission for this payment
        if (CustomerReferralTransaction::where('transaction_id', $transaction->id)->where('type', 'commission')->exists()) {
            Log::info('ReferralCommissionService: Commission already paid for transaction', [
                'transaction_id' => $transaction->id,
            ]);
            return;
        }

        $rate = ReferralConfig::customerCommissionRate();

        $commission = round((float) $transaction->amount * $rate, 2);
        if ($commission <= 0) {
            return;
        }

        $referrer = $customerReferral->referrer;
        if (!$referrer) {
            Log::warning('ReferralCommissionService: Referrer user not found', [
                'customer_referral_id' => $customerReferral->id,
            ]);
            return;
        }

        $wallet = $referrer->getOrCreateCustomerReferralWallet();
        $description = sprintf(
            '10%% commission from %s payment (Transaction #%s)',
            $this->getPayableTypeLabel($transaction),
            $transaction->transaction_ref ?? $transaction->id
        );

        $wallet->deposit(
            $commission,
            $description,
            $transaction,
            'commission',
            $customerReferral->id,
            $transaction->id
        );

        if ($customerReferral->status === 'pending') {
            $customerReferral->update(['status' => 'qualified']);
        }

        Log::info('ReferralCommissionService: Commission credited', [
            'transaction_id' => $transaction->id,
            'customer_referral_id' => $customerReferral->id,
            'referrer_user_id' => $referrer->id,
            'commission' => $commission,
            'payment_amount' => $transaction->amount,
        ]);
    }

    /**
     * Get business_id from the transaction (direct or via transactionable).
     */
    protected function getBusinessIdFromTransaction(Transaction $transaction): ?int
    {
        if ($transaction->business_id) {
            return (int) $transaction->business_id;
        }

        $payable = $transaction->transactionable;
        if (!$payable) {
            return null;
        }

        if (isset($payable->business_id)) {
            return (int) $payable->business_id;
        }

        return null;
    }

    /**
     * Human-readable label for the payable type (for commission description).
     */
    protected function getPayableTypeLabel(Transaction $transaction): string
    {
        $payable = $transaction->transactionable;
        if (!$payable) {
            return 'payment';
        }

        return match (get_class($payable)) {
            \App\Models\Subscription::class => 'subscription',
            \App\Models\AdCampaign::class => 'ad campaign',
            \App\Models\Wallet::class => 'wallet',
            default => 'payment',
        };
    }
}
