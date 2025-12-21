<?php

namespace App\Events;

use App\Models\Batch;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BatchExpired
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Batch $batch,
        public User $expiredBy
    ) {}
}
