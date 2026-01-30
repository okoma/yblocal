<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Wallet;

class WalletActivityObserver
{
    public function created(Wallet $wallet): void
    {
        ActivityLog::log(
            'created',
            'Wallet created for user #' . $wallet->user_id,
            $wallet,
            ['attributes' => $this->filteredAttributes($wallet)]
        );
    }

    public function updated(Wallet $wallet): void
    {
        $changes = $this->buildChanges($wallet);
        if ($changes === null) {
            return;
        }

        $description = 'Wallet updated for user #' . $wallet->user_id;

        if (isset($changes['old']['balance'], $changes['new']['balance'])) {
            $description = 'Wallet balance changed for user #' . $wallet->user_id .
                ' (' . $changes['old']['balance'] . ' → ' . $changes['new']['balance'] . ')';
        }

        ActivityLog::log('updated', $description, $wallet, ['changes' => $changes]);
    }

    public function deleted(Wallet $wallet): void
    {
        ActivityLog::log(
            'deleted',
            'Wallet deleted for user #' . $wallet->user_id,
            $wallet,
            ['attributes' => $this->filteredAttributes($wallet)]
        );
    }

    protected function buildChanges(Wallet $wallet): ?array
    {
        $dirty = $wallet->getDirty();
        unset($dirty['updated_at']);

        if (empty($dirty)) {
            return null;
        }

        $old = [];
        $new = [];

        foreach ($dirty as $key => $value) {
            $old[$key] = $wallet->getOriginal($key);
            $new[$key] = $value;
        }

        return ['old' => $old, 'new' => $new];
    }

    protected function filteredAttributes(Wallet $wallet): array
    {
        $keys = [
            'user_id',
            'business_id',
            'balance',
            'currency',
            'ad_credits',
            'quote_credits',
        ];

        return array_intersect_key($wallet->getAttributes(), array_flip($keys));
    }
}
