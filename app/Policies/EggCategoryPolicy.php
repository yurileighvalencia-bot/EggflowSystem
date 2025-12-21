<?php

namespace App\Policies;

use App\Models\EggCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EggCategoryPolicy
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
        // Everyone can view categories
        return true;
    }

    public function view(User $user, EggCategory $category): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('manage-categories');
    }

    public function update(User $user, EggCategory $category): bool
    {
        return $user->can('manage-categories');
    }

    public function delete(User $user, EggCategory $category): bool
    {
        return $user->can('manage-categories');
    }
}
