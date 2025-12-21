<?php

namespace App\Listeners;

use App\Events\RestockRequestCreated;
use App\Models\User;
use App\Notifications\NewRestockRequestNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotifyFarmStaffOfRestockRequest implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(RestockRequestCreated $event): void
    {
        $request = $event->restockRequest;

        // Get farm staff and managers who should be notified
        $farmStaff = User::role(['farm_staff', 'manager'])
            ->where('is_active', true)
            ->get();

        if ($farmStaff->isEmpty()) {
            Log::warning("No farm staff to notify for restock request #{$request->id}");
            return;
        }

        // Send notification to all farm staff
        Notification::send($farmStaff, new NewRestockRequestNotification($request));

        Log::info("Notified {$farmStaff->count()} farm staff of new restock request #{$request->id}");
    }
}
