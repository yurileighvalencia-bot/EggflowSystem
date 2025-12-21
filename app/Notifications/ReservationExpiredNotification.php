<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationExpiredNotification extends Notification implements ShouldQueue
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

        return (new MailMessage)
            ->subject('Reservation Expired - ' . $reservation->reservation_code)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Unfortunately, your reservation has expired.')
            ->line("**Reservation Code:** {$reservation->reservation_code}")
            ->line("**Original Pickup Date:** {$reservation->pickup_date->format('F j, Y')}")
            ->line('The reserved items have been released back to inventory.')
            ->line('If you still need these items, please create a new reservation.')
            ->action('Create New Reservation', url('/reservations/create'))
            ->line('We apologize for any inconvenience.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'reservation_expired',
            'reservation_id' => $this->reservation->id,
            'reservation_code' => $this->reservation->reservation_code,
            'message' => "Reservation {$this->reservation->reservation_code} has expired",
        ];
    }
}
