<?php

namespace App\Notifications;

use App\Models\RestockRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewRestockRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected RestockRequest $restockRequest
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->restockRequest;

        return (new MailMessage)
            ->subject('New Restock Request Pending')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A new restock request has been submitted and requires acknowledgement.')
            ->line("**Shop:** {$request->shop->name}")
            ->line("**Category:** {$request->eggCategory->name}")
            ->line("**Quantity Requested:** {$request->quantity_requested}")
            ->action('View Request', url('/restock-requests/' . $request->id))
            ->line('Please review and acknowledge this request at your earliest convenience.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'restock_request_created',
            'restock_request_id' => $this->restockRequest->id,
            'shop_name' => $this->restockRequest->shop->name,
            'category_name' => $this->restockRequest->eggCategory->name,
            'quantity_requested' => $this->restockRequest->quantity_requested,
            'message' => "New restock request for {$this->restockRequest->quantity_requested} {$this->restockRequest->eggCategory->name}",
        ];
    }
}
