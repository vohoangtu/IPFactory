<?php

namespace App\Modules\Simulation\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired by SimulationEventBus when a simulation event occurs (ActorDied, SpeciesExtinct,
 * CollapseTriggered, PhaseTransition, etc.). Listeners can log to Chronicle, History, or metrics.
 */
class SimulationEventOccurred
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $universeId,
        public string $type,
        public int $tick,
        public array $payload = []
    ) {}
}
