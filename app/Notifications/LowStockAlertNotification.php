<?php

namespace App\Notifications;

use App\Models\EggCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected int $shopId,
        protected EggCategory $category,
        protected int $currentStock,
        protected int $threshold
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ Low Stock Alert')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line("Stock levels are running low and require attention.")
            ->line("**Category:** {$this->category->name}")
            ->line("**Current Stock:** {$this->currentStock}")
            ->line("**Threshold:** {$this->threshold}")
            ->action('Create Restock Request', url('/restock-requests/create'))
            ->line('Consider creating a restock request to replenish inventory.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock_alert',
            'shop_id' => $this->shopId,
            'category_id' => $this->category->id,
            'category_name' => $this->category->name,
            'current_stock' => $this->currentStock,
            'threshold' => $this->threshold,
            'message' => "Low stock alert for {$this->category->name}: {$this->currentStock} remaining",
        ];
    }
}
