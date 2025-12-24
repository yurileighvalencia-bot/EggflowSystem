<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Reservation $reservation
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reservation Cancelled - ' . $this->reservation->reservation_code)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your reservation has been cancelled.')
            ->line('Reservation Code: ' . $this->reservation->reservation_code)
            ->line('Shop: ' . $this->reservation->shop?->name)
            ->line('Originally scheduled for pickup: ' . $this->reservation->pickup_date?->format('F j, Y'))
            ->line('If you did not request this cancellation, please contact us immediately.')
            ->line('Thank you for your understanding.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'reservation_cancelled',
            'reservation_id' => $this->reservation->id,
            'reservation_code' => $this->reservation->reservation_code,
            'shop_name' => $this->reservation->shop?->name,
            'message' => 'Your reservation ' . $this->reservation->reservation_code . ' has been cancelled.',
        ];
    }
}
