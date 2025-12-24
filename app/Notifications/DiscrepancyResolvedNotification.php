<?php

namespace App\Notifications;

use App\Models\DeliveryDiscrepancy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DiscrepancyResolvedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public DeliveryDiscrepancy $discrepancy
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
        $delivery = $this->discrepancy->delivery;
        $resolution = $this->discrepancy->resolution ?? 'No resolution provided';
        
        return (new MailMessage)
            ->subject("Discrepancy Resolved: Delivery #{$delivery->id}")
            ->greeting("Hello {$notifiable->name},")
            ->line("The discrepancy you reported has been investigated and resolved.")
            ->line("**Delivery:** #{$delivery->id}")
            ->line("**Type:** {$this->discrepancy->discrepancy_type}")
            ->line("**Quantity Discrepancy:** {$this->discrepancy->quantity_difference}")
            ->line("**Resolution:** {$resolution}")
            ->line("**Investigated By:** " . ($this->discrepancy->investigator?->name ?? 'System'))
            ->action('View Delivery Details', url("/deliveries/{$delivery->id}"))
            ->line('Thank you for reporting this discrepancy.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'discrepancy_resolved',
            'discrepancy_id' => $this->discrepancy->id,
            'delivery_id' => $this->discrepancy->delivery_id,
            'discrepancy_type' => $this->discrepancy->discrepancy_type,
            'resolution' => $this->discrepancy->resolution,
            'investigated_by' => $this->discrepancy->investigated_by,
            'investigated_at' => $this->discrepancy->investigated_at?->toISOString(),
            'message' => "Your reported discrepancy has been resolved.",
        ];
    }
}
