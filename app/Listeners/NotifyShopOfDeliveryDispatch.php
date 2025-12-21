<?php

namespace App\Listeners;

use App\Events\DeliveryDispatched;
use App\Models\User;
use App\Notifications\DeliveryDispatchedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotifyShopOfDeliveryDispatch implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(DeliveryDispatched $event): void
    {
        $delivery = $event->delivery;
        $shopId = $delivery->shop_id;

        // Notify shop staff
        $shopStaff = User::role(['shop_staff', 'manager'])
            ->where('is_active', true)
            ->where(function ($query) use ($shopId) {
                $query->where('shop_id', $shopId)
                    ->orWhereNull('shop_id');
            })
            ->get();

        if ($shopStaff->isEmpty()) {
            Log::warning("No shop staff to notify for dispatched delivery #{$delivery->id}");
            return;
        }

        Notification::send($shopStaff, new DeliveryDispatchedNotification($delivery));

        Log::info("Notified shop staff of dispatched delivery #{$delivery->id}");
    }
}
