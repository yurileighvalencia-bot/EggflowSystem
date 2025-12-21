<?php

namespace App\Listeners;

use App\Events\LowStockDetected;
use App\Models\User;
use App\Models\RestockRequest;
use App\Notifications\LowStockAlertNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class HandleLowStockAlert implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(LowStockDetected $event): void
    {
        $shopId = $event->shopId;
        $category = $event->category;

        // Check if there's already an active restock request
        if (RestockRequest::hasActiveRequest($shopId, $category->id)) {
            Log::info("Active restock request already exists for shop {$shopId}, category {$category->name}");
            return;
        }

        // Notify shop staff and managers
        $notifyUsers = User::role(['shop_staff', 'manager'])
            ->where('is_active', true)
            ->where(function ($query) use ($shopId) {
                $query->where('shop_id', $shopId)
                    ->orWhereNull('shop_id');
            })
            ->get();

        if ($notifyUsers->isEmpty()) {
            Log::warning("No users to notify for low stock alert");
            return;
        }

        Notification::send($notifyUsers, new LowStockAlertNotification(
            $shopId,
            $category,
            $event->currentStock,
            $event->threshold
        ));

        Log::info("Low stock alert sent for shop {$shopId}, category {$category->name}");
    }
}
