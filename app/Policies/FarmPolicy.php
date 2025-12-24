<?php

namespace App\Policies;

use App\Models\Farm;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FarmPolicy
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
        return $user->can('view-batches') || $user->can('manage-farms');
    }

    public function view(User $user, Farm $farm): bool
    {
        // Farm staff can view their own farm
        if ($user->isFarmStaff() && $user->farm_id === $farm->id) {
            return true;
        }

        return $user->can('manage-farms');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-farms');
    }

    public function update(User $user, Farm $farm): bool
    {
        return $user->can('manage-farms');
    }

    public function delete(User $user, Farm $farm): bool
    {
        return $user->can('manage-farms');
    }
}
