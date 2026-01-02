<?php

namespace App\Events;

use App\Models\RestockRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RestockRequestCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public RestockRequest $restockRequest
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Get the farm ID from the restock request's shop
        // Farm staff should receive notifications about restock requests
        return [
            new PrivateChannel('farm.' . $this->restockRequest->shop->farm_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'RestockRequestCreated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->restockRequest->id,
            'shop' => $this->restockRequest->shop->name,
            'category' => $this->restockRequest->eggCategory->name,
            'quantity' => $this->restockRequest->quantity_requested,
            'status' => $this->restockRequest->status,
        ];
    }
}
