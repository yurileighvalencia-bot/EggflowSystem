<?php

namespace App\Notifications;

use App\Models\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BatchExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Batch $batch,
        public int $wastedQuantity
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
        return (new MailMessage)
            ->subject("Batch Expired: {$this->batch->batch_code}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Batch **{$this->batch->batch_code}** has expired.")
            ->line("**Category:** {$this->batch->eggCategory->name}")
            ->line("**Wasted Quantity:** {$this->wastedQuantity} eggs")
            ->line("**Expiry Date:** {$this->batch->expires_at->format('M d, Y')}")
            ->action('View Wastage Report', url('/wastage'))
            ->line('Please review the wastage logs for more details.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'batch_expired',
            'batch_id' => $this->batch->id,
            'batch_code' => $this->batch->batch_code,
            'category' => $this->batch->eggCategory->name ?? 'Unknown',
            'wasted_quantity' => $this->wastedQuantity,
            'expired_at' => $this->batch->expires_at->toISOString(),
            'message' => "Batch {$this->batch->batch_code} expired with {$this->wastedQuantity} eggs wasted.",
        ];
    }
}
            //
        ];
    }
}
