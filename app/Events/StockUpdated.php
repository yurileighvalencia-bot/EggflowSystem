<?php

namespace App\Events;

use App\Models\Inventory;
use App\Models\Shop;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $shopId,
        public int $categoryId,
        public int $availableStock,
        public int $reservedStock,
        public string $action = 'updated' // 'added', 'sold', 'reserved', 'released', 'adjusted'
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('stock.' . $this->shopId),
            new Channel('stock.all'), // For manager views that monitor all shops
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'StockUpdated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'shop_id' => $this->shopId,
            'category_id' => $this->categoryId,
            'available_stock' => $this->availableStock,
            'reserved_stock' => $this->reservedStock,
            'action' => $this->action,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
