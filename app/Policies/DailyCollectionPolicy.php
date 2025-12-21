<?php

namespace App\Policies;

use App\Models\DailyCollection;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DailyCollectionPolicy
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
        return $user->can('view-collections');
    }

    public function view(User $user, DailyCollection $collection): bool
    {
        if (!$user->can('view-collections')) {
            return false;
        }

        // Farm staff can only view collections from their farm
        if ($user->isFarmStaff()) {
            return $collection->batch?->farm_id === $user->farm_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('create-collection');
    }

    public function update(User $user, DailyCollection $collection): bool
    {
        if (!$user->can('edit-collection')) {
            return false;
        }

        // Farm staff can only edit their own collections
        if ($user->isFarmStaff() && $collection->staff_id !== $user->id) {
            return false;
        }

        return true;
    }

    public function verify(User $user, DailyCollection $collection): bool
    {
        if (!$user->can('verify-collection')) {
            return false;
        }

        // Cannot verify already verified collections
        if ($collection->verified_at !== null) {
            return false;
        }

        // Cannot verify own collection
        if ($collection->staff_id === $user->id) {
            return false;
        }

        return true;
    }

    public function delete(User $user, DailyCollection $collection): bool
    {
        // Only unverified collections can be deleted by managers
        return false;
    }
}
