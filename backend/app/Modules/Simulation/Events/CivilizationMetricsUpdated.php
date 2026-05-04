<?php

namespace App\Modules\Simulation\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CivilizationMetricsUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $universeId,
        public int $tick,
        public array $metrics
    ) {}
}
