<?php

namespace App\Listeners;

use App\Events\ReservationCancelled;
use App\Notifications\ReservationCancelledNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyCustomerOfCancelledReservation implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(ReservationCancelled $event): void
    {
        $reservation = $event->reservation;
        $customer = $reservation->customer;

        if ($customer) {
            $customer->notify(new ReservationCancelledNotification($reservation));
        }
    }
}
