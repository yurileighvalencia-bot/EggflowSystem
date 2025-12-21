<?php

namespace App\Events;

use App\Models\Inventory;
use App\Models\EggCategory;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockDetected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $shopId,
        public EggCategory $category,
        public int $currentStock,
        public int $threshold
    ) {}
}
