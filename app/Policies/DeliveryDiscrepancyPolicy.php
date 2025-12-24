<?php

namespace App\Policies;

use App\Models\DeliveryDiscrepancy;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeliveryDiscrepancyPolicy
{
    use HandlesAuthorization;

    /**
     * Managers can do everything.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('manager')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view-deliveries') || $user->can('report-discrepancy');
    }

    public function view(User $user, DeliveryDiscrepancy $discrepancy): bool
    {
        if (!$user->can('view-deliveries')) {
            return false;
        }

        // Shop staff can only view discrepancies for their shop
        if ($user->isShopStaff()) {
            return $discrepancy->delivery?->shop_id === $user->shop_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('report-discrepancy');
    }

    public function investigate(User $user, DeliveryDiscrepancy $discrepancy): bool
    {
        return $user->can('investigate-discrepancy');
    }
}
