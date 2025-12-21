<?php

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InventoryPolicy
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
        return $user->can('view-inventory');
    }

    public function view(User $user, Inventory $inventory): bool
    {
        if (!$user->can('view-inventory')) {
            return false;
        }

        // Shop staff can only view their shop's inventory
        if ($user->isShopStaff() && $user->shop_id !== $inventory->shop_id) {
            return false;
        }

        return true;
    }

    public function adjust(User $user, Inventory $inventory): bool
    {
        if (!$user->can('adjust-inventory')) {
            return false;
        }

        // Shop staff can only adjust their shop's inventory
        if ($user->isShopStaff() && $user->shop_id !== $inventory->shop_id) {
            return false;
        }

        return true;
    }

    public function transfer(User $user): bool
    {
        return $user->can('transfer-inventory');
    }
}
