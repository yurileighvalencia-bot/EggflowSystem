<?php

namespace App\Notifications;

use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DiscrepancyReportedNotification extends Notification implements ShouldQueue
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
        $discrepancyCount = $delivery->discrepancies()->count();
        $missingQty = $delivery->discrepancies()->sum('qty_missing');

        return (new MailMessage)
            ->subject('⚠️ Delivery Discrepancy Reported')
            ->greeting('Attention ' . $notifiable->name . '!')
            ->line("A discrepancy has been reported on Delivery #{$delivery->id}.")
            ->line("**Shop:** {$delivery->shop->name}")
            ->line("**Number of Discrepancies:** {$discrepancyCount}")
            ->line("**Total Missing Quantity:** {$missingQty}")
            ->line("**Reported at:** " . now()->format('Y-m-d H:i'))
            ->action('Investigate Discrepancy', url('/deliveries/' . $delivery->id . '/discrepancies'))
            ->line('Please investigate this matter promptly.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'discrepancy_reported',
            'delivery_id' => $this->delivery->id,
            'shop_name' => $this->delivery->shop->name,
            'discrepancy_count' => $this->delivery->discrepancies()->count(),
            'message' => "Discrepancy reported on Delivery #{$this->delivery->id}",
        ];
    }
}
