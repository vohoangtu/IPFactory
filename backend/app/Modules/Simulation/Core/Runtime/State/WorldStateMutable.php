<?php

namespace App\Modules\Simulation\Core\Runtime\State;

/**
 * WorldStateMutable – A mutable version of WorldState for Effect application.
 * 
 * In a strict DDD/Simulation approach, Engines receive a read-only WorldState,
 * and only Effects have access to this Mutable version during the resolution phase.
 */
class WorldStateMutable extends WorldState
{
    public static function fromWorldState(WorldState $state): self
    {
        // Clone the data to ensure we have a separate copy for application
        return new self($state->toArray());
    }

    public function toWorldState(): WorldState
    {
        return new WorldState($this->toArray());
    }

    public function setStateVector(array $data): void
    {
        $this->data = $data;
    }

    public function setMetrics(array $metrics): void
    {
        $this->set('metrics', $metrics);
    }
}
