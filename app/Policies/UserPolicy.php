<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
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
        return $user->can('manage-users');
    }

    public function view(User $user, User $targetUser): bool
    {
        // Users can view their own profile
        if ($user->id === $targetUser->id) {
            return true;
        }

        // Farm staff can view users at their farm
        if ($user->isFarmStaff() && $targetUser->farm_id === $user->farm_id) {
            return true;
        }

        // Shop staff can view users at their shop
        if ($user->isShopStaff() && $targetUser->shop_id === $user->shop_id) {
            return true;
        }

        return $user->can('manage-users');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-users');
    }

    public function update(User $user, User $targetUser): bool
    {
        // Users can update their own profile
        if ($user->id === $targetUser->id) {
            return true;
        }

        return $user->can('manage-users');
    }

    public function delete(User $user, User $targetUser): bool
    {
        // Prevent self-deletion
        if ($user->id === $targetUser->id) {
            return false;
        }

        return $user->can('manage-users');
    }

    public function assignRole(User $user, User $targetUser): bool
    {
        return $user->can('manage-users');
    }

    public function updatePassword(User $user, User $targetUser): bool
    {
        // Users can update their own password
        if ($user->id === $targetUser->id) {
            return true;
        }

        return $user->can('manage-users');
    }
}
