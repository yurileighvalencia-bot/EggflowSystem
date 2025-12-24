<?php

namespace App\Listeners;

use App\Events\BatchExpired;
use App\Models\User;
use App\Notifications\BatchExpiredNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Handles notifications when a batch expires.
 * 
 * NOTE: Wastage logging is handled by CheckBatchExpiry command (the authoritative source).
 * This listener is purely for notifications to managers/staff.
 */
class LogExpiredBatchWastage implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(BatchExpired $event): void
    {
        $batch = $event->batch;
        $wastedQuantity = $event->quantity;

        // Notify farm managers about the expired batch
        $managers = User::role('manager')
            ->where(function ($query) use ($batch) {
                $query->whereNull('farm_id')
                    ->orWhere('farm_id', $batch->farm_id);
            })
            ->get();

        if ($managers->isNotEmpty()) {
            Notification::send($managers, new BatchExpiredNotification($batch, $wastedQuantity));
        }

        Log::info("BatchExpired notification sent for batch {$batch->batch_code} ({$wastedQuantity} eggs wasted).");
    }
}
