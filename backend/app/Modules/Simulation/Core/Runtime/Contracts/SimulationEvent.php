<?php

namespace App\Modules\Simulation\Core\Runtime\Contracts;

/**
 * Interface for all simulation output events.
 */
interface SimulationEvent
{
    public function type(): string;
    public function payload(): array;
}
