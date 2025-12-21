<?php

namespace App\Policies;

use App\Models\WastageLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class WastageLogPolicy
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
        return $user->can('view-wastage');
    }

    public function view(User $user, WastageLog $log): bool
    {
        if (!$user->can('view-wastage')) {
            return false;
        }

        // Shop staff can only view their shop's wastage
        if ($user->isShopStaff() && $user->shop_id !== $log->shop_id) {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('log-wastage');
    }

    public function update(User $user, WastageLog $log): bool
    {
        // Wastage logs cannot be edited, only new ones created
        return false;
    }

    public function delete(User $user, WastageLog $log): bool
    {
        // Only managers can delete wastage logs (handled by before)
        return false;
    }
}
