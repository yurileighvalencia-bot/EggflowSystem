<?php

namespace App\Listeners;

use App\Events\ReservationExpired;
use App\Notifications\ReservationExpiredNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifyCustomerOfExpiredReservation implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(ReservationExpired $event): void
    {
        $reservation = $event->reservation;
        $customer = $reservation->customer;

        if (!$customer) {
            Log::warning("No customer to notify for expired reservation #{$reservation->id}");
            return;
        }

        $customer->notify(new ReservationExpiredNotification($reservation));

        Log::info("Notified customer {$customer->email} of expired reservation #{$reservation->id}");
    }
}
