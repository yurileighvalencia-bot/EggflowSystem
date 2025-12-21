<?php

namespace App\Events;

use App\Models\DeliveryDiscrepancy;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DiscrepancyInvestigated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public DeliveryDiscrepancy $discrepancy
    ) {}
}
