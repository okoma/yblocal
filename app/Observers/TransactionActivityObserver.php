<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Transaction;

class TransactionActivityObserver
{
    public function created(Transaction $transaction): void
    {
        ActivityLog::log(
            'created',
            'Transaction created: ' . $transaction->transaction_ref,
            $transaction,
            ['attributes' => $this->filteredAttributes($transaction)]
        );
    }

    public function updated(Transaction $transaction): void
    {
        $changes = $this->buildChanges($transaction);
        if ($changes === null) {
            return;
        }

        $description = 'Transaction updated: ' . $transaction->transaction_ref;

        if (isset($changes['old']['status'], $changes['new']['status'])) {
            $description = 'Transaction status changed: ' . $transaction->transaction_ref .
                ' (' . $changes['old']['status'] . ' → ' . $changes['new']['status'] . ')';
        }

        ActivityLog::log('updated', $description, $transaction, ['changes' => $changes]);
    }

    public function deleted(Transaction $transaction): void
    {
        ActivityLog::log(
            'deleted',
            'Transaction deleted: ' . $transaction->transaction_ref,
            $transaction,
            ['attributes' => $this->filteredAttributes($transaction)]
        );
    }

    public function restored(Transaction $transaction): void
    {
        ActivityLog::log(
            'restored',
            'Transaction restored: ' . $transaction->transaction_ref,
            $transaction,
            ['attributes' => $this->filteredAttributes($transaction)]
        );
    }

    protected function buildChanges(Transaction $transaction): ?array
    {
        $dirty = $transaction->getDirty();
        unset($dirty['updated_at']);

        if (empty($dirty)) {
            return null;
        }

        $old = [];
        $new = [];

        foreach ($dirty as $key => $value) {
            $old[$key] = $transaction->getOriginal($key);
            $new[$key] = $value;
        }

        return ['old' => $old, 'new' => $new];
    }

    protected function filteredAttributes(Transaction $transaction): array
    {
        $keys = [
            'user_id',
            'business_id',
            'transaction_ref',
            'payment_gateway_id',
            'transactionable_type',
            'transactionable_id',
            'amount',
            'currency',
            'payment_method',
            'status',
        ];

        return array_intersect_key($transaction->getAttributes(), array_flip($keys));
    }
}
