<?php

namespace App\Services;

use App\Models\SubscriptionPlan;

/**
 * Shadow limits for the Create Business wizard only.
 * Uses the free plan; no business/subscription exists yet.
 * Edit and elsewhere use the business's actual subscription.
 */
class NewBusinessPlanLimits
{
    protected static ?SubscriptionPlan $plan = null;

    public function plan(): ?SubscriptionPlan
    {
        if (self::$plan !== null) {
            return self::$plan;
        }
        self::$plan = SubscriptionPlan::where('slug', 'free')->where('is_active', true)->first();
        return self::$plan;
    }

    public function maxFaqs(): ?int
    {
        $p = $this->plan();
        return $p ? $p->max_faqs : null;
    }

    public function maxPhotos(): ?int
    {
        $p = $this->plan();
        return $p ? $p->max_photos : null;
    }

    public function maxLeadsView(): ?int
    {
        $p = $this->plan();
        return $p ? $p->max_leads_view : null;
    }

    public function maxProducts(): ?int
    {
        $p = $this->plan();
        return $p ? $p->max_products : null;
    }

    public function maxTeamMembers(): ?int
    {
        $p = $this->plan();
        return $p ? $p->max_team_members : null;
    }
}
