<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalePolicy
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
        return $user->can('view-sales');
    }

    public function view(User $user, Sale $sale): bool
    {
        if (!$user->can('view-sales')) {
            return false;
        }

        // Customers can only view their own sales
        if ($user->isCustomer() && $sale->customer_id !== $user->id) {
            return false;
        }

        // Shop staff can only view their shop's sales
        if ($user->isShopStaff() && $user->shop_id !== $sale->shop_id) {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('create-sale');
    }

    public function void(User $user, Sale $sale): bool
    {
        if (!$user->can('void-sale')) {
            return false;
        }

        // Can only void completed sales
        if ($sale->status !== Sale::STATUS_COMPLETED) {
            return false;
        }

        // Shop staff can only void their shop's sales
        if ($user->isShopStaff() && $user->shop_id !== $sale->shop_id) {
            return false;
        }

        return true;
    }

    public function refund(User $user, Sale $sale): bool
    {
        if (!$user->can('refund-sale')) {
            return false;
        }

        // Can only refund completed sales
        if ($sale->status !== Sale::STATUS_COMPLETED) {
            return false;
        }

        return true;
    }

    public function delete(User $user, Sale $sale): bool
    {
        // Sales cannot be deleted, only voided
        return false;
    }
}
