<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Subscription;

class SubscriptionActivityObserver
{
    public function created(Subscription $subscription): void
    {
        ActivityLog::log(
            'created',
            'Subscription created: ' . $this->subscriptionLabel($subscription),
            $subscription,
            ['attributes' => $this->filteredAttributes($subscription)]
        );
    }

    public function updated(Subscription $subscription): void
    {
        $changes = $this->buildChanges($subscription);
        if ($changes === null) {
            return;
        }

        $description = 'Subscription updated: ' . $this->subscriptionLabel($subscription);

        if (isset($changes['old']['status'], $changes['new']['status'])) {
            $description = 'Subscription status changed: ' . $this->subscriptionLabel($subscription) .
                ' (' . $changes['old']['status'] . ' → ' . $changes['new']['status'] . ')';
        }

        ActivityLog::log('updated', $description, $subscription, ['changes' => $changes]);
    }

    public function deleted(Subscription $subscription): void
    {
        ActivityLog::log(
            'deleted',
            'Subscription deleted: ' . $this->subscriptionLabel($subscription),
            $subscription,
            ['attributes' => $this->filteredAttributes($subscription)]
        );
    }

    public function restored(Subscription $subscription): void
    {
        ActivityLog::log(
            'restored',
            'Subscription restored: ' . $this->subscriptionLabel($subscription),
            $subscription,
            ['attributes' => $this->filteredAttributes($subscription)]
        );
    }

    protected function buildChanges(Subscription $subscription): ?array
    {
        $dirty = $subscription->getDirty();
        unset($dirty['updated_at']);

        if (empty($dirty)) {
            return null;
        }

        $old = [];
        $new = [];

        foreach ($dirty as $key => $value) {
            $old[$key] = $subscription->getOriginal($key);
            $new[$key] = $value;
        }

        return ['old' => $old, 'new' => $new];
    }

    protected function filteredAttributes(Subscription $subscription): array
    {
        $keys = [
            'business_id',
            'user_id',
            'subscription_plan_id',
            'subscription_code',
            'billing_interval',
            'status',
            'starts_at',
            'ends_at',
            'trial_ends_at',
            'auto_renew',
        ];

        return array_intersect_key($subscription->getAttributes(), array_flip($keys));
    }

    protected function subscriptionLabel(Subscription $subscription): string
    {
        return $subscription->subscription_code ?: ('#' . $subscription->id);
    }
}
