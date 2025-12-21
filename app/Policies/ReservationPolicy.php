<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReservationPolicy
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
        return $user->can('view-reservations');
    }

    public function view(User $user, Reservation $reservation): bool
    {
        if (!$user->can('view-reservations')) {
            return false;
        }

        // Customers can only view their own reservations
        if ($user->isCustomer() && $reservation->customer_id !== $user->id) {
            return false;
        }

        // Shop staff can only view their shop's reservations
        if ($user->isShopStaff() && $user->shop_id !== $reservation->shop_id) {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('create-reservation');
    }

    public function update(User $user, Reservation $reservation): bool
    {
        if (!$user->can('edit-reservation')) {
            return false;
        }

        // Can only edit pending reservations
        if ($reservation->status !== Reservation::STATUS_PENDING) {
            return false;
        }

        // Customers can only edit their own reservations
        if ($user->isCustomer() && $reservation->customer_id !== $user->id) {
            return false;
        }

        return true;
    }

    public function confirm(User $user, Reservation $reservation): bool
    {
        if (!$user->can('confirm-reservation')) {
            return false;
        }

        return $reservation->status === Reservation::STATUS_PENDING;
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        if (!$user->can('cancel-reservation')) {
            return false;
        }

        // Can only cancel pending or confirmed reservations
        if (!in_array($reservation->status, [
            Reservation::STATUS_PENDING,
            Reservation::STATUS_CONFIRMED,
        ])) {
            return false;
        }

        // Customers can only cancel their own reservations
        if ($user->isCustomer() && $reservation->customer_id !== $user->id) {
            return false;
        }

        return true;
    }

    public function fulfill(User $user, Reservation $reservation): bool
    {
        if (!$user->can('fulfill-reservation')) {
            return false;
        }

        // Can fulfill confirmed or ready reservations
        return in_array($reservation->status, [
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_READY,
        ]);
    }

    public function delete(User $user, Reservation $reservation): bool
    {
        // Only pending reservations by the owner can be deleted
        if ($reservation->status !== Reservation::STATUS_PENDING) {
            return false;
        }

        if ($user->isCustomer()) {
            return $reservation->customer_id === $user->id;
        }

        return false;
    }
}
