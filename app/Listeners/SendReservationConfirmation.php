<?php

namespace App\Listeners;

use App\Events\ReservationCreated;
use App\Notifications\ReservationConfirmationNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendReservationConfirmation implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(ReservationCreated $event): void
    {
        $reservation = $event->reservation;
        $customer = $reservation->customer;

        if (!$customer) {
            Log::warning("No customer to notify for reservation #{$reservation->id}");
            return;
        }

        $customer->notify(new ReservationConfirmationNotification($reservation));

        Log::info("Sent reservation confirmation to customer {$customer->email} for reservation #{$reservation->id}");
    }
}
