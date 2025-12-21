<?php

namespace App\Listeners;

use App\Events\BatchExpired;
use App\Models\WastageLog;
use App\Models\Inventory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogExpiredBatchWastage implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(BatchExpired $event): void
    {
        $batch = $event->batch;

        if ($batch->current_quantity <= 0) {
            return;
        }

        // Log wastage for remaining quantity in the batch
        WastageLog::create([
            'shop_id' => null, // Batch expired at farm level
            'batch_id' => $batch->id,
            'delivery_id' => null,
            'egg_category_id' => $batch->egg_category_id,
            'quantity' => $batch->current_quantity,
            'source' => WastageLog::SOURCE_BATCH_EXPIRED,
            'reason' => "Batch {$batch->batch_code} expired on {$batch->expires_at->format('Y-m-d')}",
            'logged_by' => $event->expiredBy->id,
            'logged_at' => now(),
        ]);

        // Also log wastage for each shop's inventory of this batch
        $inventories = Inventory::where('batch_id', $batch->id)
            ->where('available_stock', '>', 0)
            ->get();

        foreach ($inventories as $inventory) {
            WastageLog::create([
                'shop_id' => $inventory->shop_id,
                'batch_id' => $batch->id,
                'delivery_id' => null,
                'egg_category_id' => $batch->egg_category_id,
                'quantity' => $inventory->available_stock,
                'source' => WastageLog::SOURCE_BATCH_EXPIRED,
                'reason' => "Batch {$batch->batch_code} expired - inventory cleared",
                'logged_by' => $event->expiredBy->id,
                'logged_at' => now(),
            ]);

            // Clear the inventory
            $inventory->available_stock = 0;
            $inventory->save();
        }

        Log::info("Batch {$batch->batch_code} expired. Wastage logged for {$batch->current_quantity} eggs.");
    }
}
