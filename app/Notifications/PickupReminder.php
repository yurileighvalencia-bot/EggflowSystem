<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PickupReminder extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Reservation $reservation
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $hoursLeft = now()->diffInHours($this->reservation->pickup_deadline);
        
        $itemsList = $this->reservation->items
            ->map(fn ($item) => "• {$item->quantity}x {$item->eggCategory->name}")
            ->join("\n");

        return (new MailMessage)
            ->subject("Reminder: Pickup Your Reservation #{$this->reservation->id}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("This is a friendly reminder that your egg reservation is due for pickup soon.")
            ->line("**Reservation #{$this->reservation->id}**")
            ->line("**Pickup Deadline:** {$this->reservation->pickup_deadline->format('F j, Y \\a\\t g:i A')}")
            ->line("**Time Remaining:** Approximately {$hoursLeft} hours")
            ->line("**Pickup Location:** {$this->reservation->shop->name}")
            ->line('')
            ->line('**Your Reserved Items:**')
            ->line($itemsList)
            ->line('')
            ->line("**Total Amount:** ₱" . number_format($this->reservation->total_amount, 2))
            ->line('')
            ->line('Please pick up your order before the deadline to avoid cancellation.')
            ->salutation('Thank you for choosing EggFlow!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'pickup_reminder',
            'reservation_id' => $this->reservation->id,
            'shop_id' => $this->reservation->shop_id,
            'shop_name' => $this->reservation->shop->name,
            'pickup_deadline' => $this->reservation->pickup_deadline->toISOString(),
            'total_amount' => $this->reservation->total_amount,
            'items_count' => $this->reservation->items->count(),
            'message' => "Reminder: Your reservation #{$this->reservation->id} is due for pickup by {$this->reservation->pickup_deadline->format('M j, g:i A')}",
        ];
    }
}
