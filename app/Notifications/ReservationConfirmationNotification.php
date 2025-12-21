<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Reservation $reservation
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reservation = $this->reservation;
        $items = $reservation->items->map(function ($item) {
            return "- {$item->eggCategory->name}: {$item->quantity} @ ₱{$item->unit_price}";
        })->join("\n");

        return (new MailMessage)
            ->subject('Reservation Confirmation - ' . $reservation->reservation_code)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your reservation has been successfully created.')
            ->line("**Reservation Code:** {$reservation->reservation_code}")
            ->line("**Pickup Date:** {$reservation->pickup_date->format('F j, Y')}")
            ->line("**Shop:** {$reservation->shop->name}")
            ->line("**Items:**")
            ->line($items)
            ->line("**Total:** ₱{$reservation->total}")
            ->action('View Reservation', url('/reservations/' . $reservation->id))
            ->line('Please arrive at the scheduled pickup time. The reservation expires 24 hours before pickup if not confirmed.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'reservation_created',
            'reservation_id' => $this->reservation->id,
            'reservation_code' => $this->reservation->reservation_code,
            'pickup_date' => $this->reservation->pickup_date->toDateString(),
            'total' => $this->reservation->total,
            'message' => "Reservation {$this->reservation->reservation_code} confirmed for pickup on {$this->reservation->pickup_date->format('M j, Y')}",
        ];
    }
}
