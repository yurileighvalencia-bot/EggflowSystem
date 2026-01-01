<?php

namespace App\Policies;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShiftPolicy
{
    use HandlesAuthorization;

    /**
     * Managers can always access shifts.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('manager')) {
            return true;
        }
        return null;
    }

    /**
     * Determine whether the user can view any shifts.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-shifts') || $user->isShopStaff();
    }

    /**
     * Determine whether the user can view the shift.
     */
    public function view(User $user, Shift $shift): bool
    {
        // Staff can only view their own shifts or shifts at their shop
        if ($user->isShopStaff()) {
            return $shift->user_id === $user->id || $shift->shop_id === $user->shop_id;
        }

        return $user->can('view-shifts');
    }

    /**
     * Determine whether the user can create shifts.
     */
    public function create(User $user): bool
    {
        // Shop staff can open their own shifts
        return $user->can('create-shifts') || $user->isShopStaff();
    }

    /**
     * Determine whether the user can update the shift.
     */
    public function update(User $user, Shift $shift): bool
    {
        // Staff can only close their own shifts
        if ($user->isShopStaff()) {
            return $shift->user_id === $user->id && $shift->isOpen();
        }

        return $user->can('update-shifts');
    }

    /**
     * Determine whether the user can delete the shift.
     */
    public function delete(User $user, Shift $shift): bool
    {
        // Only managers can delete shifts
        return $user->can('delete-shifts');
    }

    /**
     * Determine whether the user can view shift discrepancies.
     */
    public function viewDiscrepancies(User $user): bool
    {
        // Only managers can view discrepancies
        return $user->hasRole('manager');
    }

    /**
     * Determine whether the user can create adjustments.
     */
    public function createAdjustment(User $user, Shift $shift): bool
    {
        // Staff can only adjust their own open shifts
        if ($user->isShopStaff()) {
            return $shift->user_id === $user->id && $shift->isOpen();
        }

        return $user->can('create-shift-adjustments');
    }
}
