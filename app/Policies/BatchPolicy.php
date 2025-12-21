<?php

namespace App\Policies;

use App\Models\Batch;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BatchPolicy
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
        return $user->can('view-batches');
    }

    public function view(User $user, Batch $batch): bool
    {
        if (!$user->can('view-batches')) {
            return false;
        }

        // Farm staff can only view batches from their farm
        if ($user->isFarmStaff() && $user->farm_id !== $batch->farm_id) {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('create-batch');
    }

    public function update(User $user, Batch $batch): bool
    {
        if (!$user->can('edit-batch')) {
            return false;
        }

        // Only allow editing active batches
        if ($batch->status !== 'active') {
            return false;
        }

        // Farm staff can only edit their farm's batches
        if ($user->isFarmStaff() && $user->farm_id !== $batch->farm_id) {
            return false;
        }

        return true;
    }

    public function delete(User $user, Batch $batch): bool
    {
        // Only managers can delete (handled by before())
        return false;
    }

    public function expire(User $user, Batch $batch): bool
    {
        return $user->can('expire-batch') && $batch->status === 'active';
    }
}
