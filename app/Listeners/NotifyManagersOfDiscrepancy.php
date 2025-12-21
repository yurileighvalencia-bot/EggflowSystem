<?php

namespace App\Listeners;

use App\Events\DeliveryDiscrepancyReported;
use App\Models\User;
use App\Notifications\DiscrepancyReportedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotifyManagersOfDiscrepancy implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(DeliveryDiscrepancyReported $event): void
    {
        $delivery = $event->delivery;

        // Notify managers only
        $managers = User::role('manager')
            ->where('is_active', true)
            ->get();

        if ($managers->isEmpty()) {
            Log::warning("No managers to notify for delivery discrepancy #{$delivery->id}");
            return;
        }

        Notification::send($managers, new DiscrepancyReportedNotification($delivery));

        Log::info("Notified managers of discrepancy on delivery #{$delivery->id}");
    }
}
