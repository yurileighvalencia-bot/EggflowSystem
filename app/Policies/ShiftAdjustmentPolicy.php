<?php

namespace App\Policies;

use App\Models\Shift;
use App\Models\ShiftAdjustment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShiftAdjustmentPolicy
{
    use HandlesAuthorization;

    /**
     * Managers can always access shift adjustments.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('manager')) {
            return true;
        }
        return null;
    }

    /**
     * Determine whether the user can view any shift adjustments.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-shift-adjustments') || $user->isShopStaff();
    }

    /**
     * Determine whether the user can view the shift adjustment.
     */
    public function view(User $user, ShiftAdjustment $adjustment): bool
    {
        // Staff can view adjustments for their own shifts
        if ($user->isShopStaff()) {
            return $adjustment->shift->user_id === $user->id;
        }

        return $user->can('view-shift-adjustments');
    }

    /**
     * Determine whether the user can create shift adjustments.
     */
    public function create(User $user): bool
    {
        return $user->can('create-shift-adjustments') || $user->isShopStaff();
    }

    /**
     * Determine whether the user can delete the shift adjustment.
     */
    public function delete(User $user, ShiftAdjustment $adjustment): bool
    {
        // Only managers can delete adjustments
        return $user->can('delete-shift-adjustments');
    }
}
