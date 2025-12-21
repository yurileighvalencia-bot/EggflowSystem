<?php

namespace App\Events;

use App\Models\RestockRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RestockRequestAcknowledged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public RestockRequest $restockRequest
    ) {}
}
