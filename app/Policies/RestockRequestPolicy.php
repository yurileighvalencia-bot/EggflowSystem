<?php

namespace App\Policies;

use App\Models\RestockRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RestockRequestPolicy
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
        return $user->can('view-restock-requests');
    }

    public function view(User $user, RestockRequest $request): bool
    {
        if (!$user->can('view-restock-requests')) {
            return false;
        }

        // Shop staff can only view their shop's requests
        if ($user->isShopStaff() && $user->shop_id !== $request->shop_id) {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('request-restock');
    }

    public function acknowledge(User $user, RestockRequest $request): bool
    {
        if (!$user->can('acknowledge-restock')) {
            return false;
        }

        // Can only acknowledge pending requests
        return $request->status === RestockRequest::STATUS_PENDING;
    }

    public function cancel(User $user, RestockRequest $request): bool
    {
        if (!$user->can('cancel-restock')) {
            return false;
        }

        // Can only cancel pending or acknowledged requests
        return in_array($request->status, [
            RestockRequest::STATUS_PENDING,
            RestockRequest::STATUS_ACKNOWLEDGED,
        ]);
    }

    public function delete(User $user, RestockRequest $request): bool
    {
        // Only pending requests can be deleted
        return $request->status === RestockRequest::STATUS_PENDING;
    }
}
