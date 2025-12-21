<?php

namespace App\Notifications;

use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeliveryDispatchedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Delivery $delivery
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $delivery = $this->delivery;
        $totalItems = $delivery->items->sum('qty_sent');

        return (new MailMessage)
            ->subject('Delivery Dispatched - On Its Way!')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A delivery has been dispatched to your location.')
            ->line("**Total Items:** {$totalItems} eggs")
            ->line("**Dispatched at:** {$delivery->dispatched_at->format('Y-m-d H:i')}")
            ->line("**Dispatched by:** {$delivery->dispatcher->name}")
            ->action('View Delivery', url('/deliveries/' . $delivery->id))
            ->line('Please prepare to receive and confirm the delivery upon arrival.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'delivery_dispatched',
            'delivery_id' => $this->delivery->id,
            'total_items' => $this->delivery->items->sum('qty_sent'),
            'dispatched_at' => $this->delivery->dispatched_at->toIso8601String(),
            'message' => "Delivery #{$this->delivery->id} has been dispatched",
        ];
    }
}
