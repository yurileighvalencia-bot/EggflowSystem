<?php

namespace App\Policies;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShopPolicy
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
        return $user->can('view-inventory') || $user->can('manage-shops');
    }

    public function view(User $user, Shop $shop): bool
    {
        // Shop staff can view their own shop
        if ($user->isShopStaff() && $user->shop_id === $shop->id) {
            return true;
        }

        // Farm staff can view shops linked to their farm
        if ($user->isFarmStaff() && $shop->farm_id === $user->farm_id) {
            return true;
        }

        return $user->can('manage-shops');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-shops');
    }

    public function update(User $user, Shop $shop): bool
    {
        return $user->can('manage-shops');
    }

    public function delete(User $user, Shop $shop): bool
    {
        return $user->can('manage-shops');
    }
}
