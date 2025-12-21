<?php

namespace App\Notifications;

use App\Models\RestockRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RestockRequestAcknowledgedNotification extends Notification implements ShouldQueue
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
            ->subject('Restock Request Acknowledged')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your restock request has been acknowledged by farm staff.')
            ->line("**Category:** {$request->eggCategory->name}")
            ->line("**Quantity:** {$request->quantity_requested}")
            ->line("**Acknowledged by:** {$request->acknowledger->name}")
            ->action('View Request', url('/restock-requests/' . $request->id))
            ->line('A delivery will be dispatched soon.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'restock_request_acknowledged',
            'restock_request_id' => $this->restockRequest->id,
            'category_name' => $this->restockRequest->eggCategory->name,
            'acknowledged_by' => $this->restockRequest->acknowledger->name,
            'message' => "Restock request for {$this->restockRequest->eggCategory->name} has been acknowledged",
        ];
    }
}
