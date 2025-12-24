<?php

namespace App\Listeners;

use App\Events\DiscrepancyInvestigated;
use App\Models\User;
use App\Notifications\DiscrepancyResolvedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifyReporterOfResolution implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(DiscrepancyInvestigated $event): void
    {
        $discrepancy = $event->discrepancy;
        
        // Notify the reporter that their discrepancy has been investigated
        $reporter = User::find($discrepancy->reported_by);
        
        if (!$reporter) {
            Log::warning("Cannot notify reporter: user {$discrepancy->reported_by} not found");
            return;
        }

        $reporter->notify(new DiscrepancyResolvedNotification($discrepancy));

        Log::info("Discrepancy resolution notification sent to user {$reporter->id} for discrepancy {$discrepancy->id}");
    }
}
