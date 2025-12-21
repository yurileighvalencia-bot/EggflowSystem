<?php

namespace App\Policies;

use App\Models\Delivery;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeliveryPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('manager')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view-deliveries');
    }

    public function view(User $user, Delivery $delivery): bool
    {
        if (!$user->can('view-deliveries')) {
            return false;
        }

        // Shop staff can only view deliveries to their shop
        if ($user->isShopStaff()) {
            return $delivery->restockRequest?->shop_id === $user->shop_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('dispatch-delivery');
    }

    public function dispatch(User $user, Delivery $delivery): bool
    {
        if (!$user->can('dispatch-delivery')) {
            return false;
        }

        return $delivery->status === Delivery::STATUS_PENDING;
    }

    public function receive(User $user, Delivery $delivery): bool
    {
        if (!$user->can('receive-delivery')) {
            return false;
        }

        // Must be in transit
        if ($delivery->status !== Delivery::STATUS_IN_TRANSIT) {
            return false;
        }

        // Shop staff can only receive deliveries for their shop
        if ($user->isShopStaff()) {
            return $delivery->restockRequest?->shop_id === $user->shop_id;
        }

        return true;
    }

    public function reportDiscrepancy(User $user, Delivery $delivery): bool
    {
        if (!$user->can('report-discrepancy')) {
            return false;
        }

        // Can only report discrepancies for delivered items
        return in_array($delivery->status, [
            Delivery::STATUS_DELIVERED,
            Delivery::STATUS_PARTIAL,
        ]);
    }
}
