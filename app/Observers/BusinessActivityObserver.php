<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Business;

class BusinessActivityObserver
{
    public function created(Business $business): void
    {
        ActivityLog::log(
            'created',
            'Business created: ' . $business->business_name,
            $business,
            ['attributes' => $this->filteredAttributes($business)]
        );
    }

    public function updated(Business $business): void
    {
        $changes = $this->buildChanges($business);
        if ($changes === null) {
            return;
        }

        $description = 'Business updated: ' . $business->business_name;

        if (isset($changes['old']['status'], $changes['new']['status'])) {
            $description = 'Business status changed: ' . $business->business_name .
                ' (' . $changes['old']['status'] . ' → ' . $changes['new']['status'] . ')';
        }

        if (isset($changes['old']['is_verified'], $changes['new']['is_verified'])) {
            $description = 'Business verification changed: ' . $business->business_name;
        }

        ActivityLog::log('updated', $description, $business, ['changes' => $changes]);
    }

    public function deleted(Business $business): void
    {
        ActivityLog::log(
            'deleted',
            'Business deleted: ' . $business->business_name,
            $business,
            ['attributes' => $this->filteredAttributes($business)]
        );
    }

    public function restored(Business $business): void
    {
        ActivityLog::log(
            'restored',
            'Business restored: ' . $business->business_name,
            $business,
            ['attributes' => $this->filteredAttributes($business)]
        );
    }

    protected function buildChanges(Business $business): ?array
    {
        $dirty = $business->getDirty();
        unset($dirty['updated_at']);

        if (empty($dirty)) {
            return null;
        }

        $old = [];
        $new = [];

        foreach ($dirty as $key => $value) {
            $old[$key] = $business->getOriginal($key);
            $new[$key] = $value;
        }

        return ['old' => $old, 'new' => $new];
    }

    protected function filteredAttributes(Business $business): array
    {
        $keys = [
            'user_id',
            'business_name',
            'status',
            'is_verified',
            'is_claimed',
            'is_premium',
            'state',
            'city',
        ];

        return array_intersect_key($business->getAttributes(), array_flip($keys));
    }
}
