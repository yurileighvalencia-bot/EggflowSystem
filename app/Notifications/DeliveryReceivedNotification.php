<?php

namespace App\Notifications;

use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeliveryReceivedNotification extends Notification implements ShouldQueue
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
        $hasDiscrepancies = $delivery->hasDiscrepancies();

        $mail = (new MailMessage)
            ->subject('Delivery Received' . ($hasDiscrepancies ? ' - With Discrepancies' : ''))
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line("Delivery #{$delivery->id} has been received.")
            ->line("**Received at:** {$delivery->received_at->format('Y-m-d H:i')}")
            ->line("**Received by:** {$delivery->receiver->name}")
            ->line("**Total Sent:** {$delivery->total_sent}")
            ->line("**Total Received:** {$delivery->total_received}")
            ->line("**Total Rejected:** {$delivery->total_rejected}");

        if ($hasDiscrepancies) {
            $mail->line('⚠️ **Note:** This delivery has discrepancies that require investigation.');
        }

        return $mail->action('View Delivery', url('/deliveries/' . $delivery->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'delivery_received',
            'delivery_id' => $this->delivery->id,
            'total_received' => $this->delivery->total_received,
            'has_discrepancies' => $this->delivery->hasDiscrepancies(),
            'message' => "Delivery #{$this->delivery->id} has been received",
        ];
    }
}
