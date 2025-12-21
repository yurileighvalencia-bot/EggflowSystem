<?php

namespace App\Listeners;

use App\Events\DeliveryReceived;
use App\Models\User;
use App\Notifications\DeliveryReceivedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotifyFarmOfDeliveryReceipt implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(DeliveryReceived $event): void
    {
        $delivery = $event->delivery;

        // Notify the dispatcher and farm staff
        $notifyUsers = User::role(['farm_staff', 'manager'])
            ->where('is_active', true)
            ->get();

        if ($notifyUsers->isEmpty()) {
            Log::warning("No farm staff to notify for received delivery #{$delivery->id}");
            return;
        }

        Notification::send($notifyUsers, new DeliveryReceivedNotification($delivery));

        Log::info("Notified farm staff of received delivery #{$delivery->id}");
    }
}
