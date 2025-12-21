<?php

namespace App\Listeners;

use App\Events\RestockRequestAcknowledged;
use App\Models\User;
use App\Notifications\RestockRequestAcknowledgedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotifyShopOfAcknowledgement implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(RestockRequestAcknowledged $event): void
    {
        $request = $event->restockRequest;

        // Notify shop staff
        $shopStaff = User::role(['shop_staff', 'manager'])
            ->where('is_active', true)
            ->where(function ($query) use ($request) {
                $query->where('shop_id', $request->shop_id)
                    ->orWhereNull('shop_id'); // Managers
            })
            ->get();

        if ($shopStaff->isEmpty()) {
            Log::warning("No shop staff to notify for acknowledged restock request #{$request->id}");
            return;
        }

        Notification::send($shopStaff, new RestockRequestAcknowledgedNotification($request));

        Log::info("Notified shop staff of acknowledged restock request #{$request->id}");
    }
}
