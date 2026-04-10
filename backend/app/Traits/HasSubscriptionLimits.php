<?php

namespace App\Traits;

use App\Models\SubscriptionPlan;

trait HasSubscriptionLimits
{
    /**
     * Get the currently active plan for the supplier.
     */
    public function activePlan()
    {
        $activeSub = $this->activeSubscription;
        return $activeSub ? $activeSub->plan : null;
    }

    /**
     * Check if the active plan has a specific feature enabled.
     */
    public function hasFeature(string $feature): bool
    {
        $plan = $this->activePlan();
        if (!$plan) {
            return false;
        }

        $features = $plan->features ?? [];
        return in_array($feature, $features);
    }

    /**
     * Check if the supplier can add more users based on their plan limit.
     */
    public function canAddUser(): bool
    {
        $plan = $this->activePlan();
        if (!$plan) {
            return false;
        }

        if (is_null($plan->max_users)) {
            return true;
        }

        $currentUsersCount = $this->users()->count();
        return $currentUsersCount < $plan->max_users;
    }

    /**
     * Check if the supplier can add more customers based on their plan limit.
     */
    public function canAddCustomer(): bool
    {
        $plan = $this->activePlan();
        if (!$plan) {
            return false;
        }

        if (is_null($plan->max_customers)) {
            return true;
        }

        $currentCustomersCount = $this->customers()->count();
        return $currentCustomersCount < $plan->max_customers;
    }
}
