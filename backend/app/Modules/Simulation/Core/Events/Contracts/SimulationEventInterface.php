<?php

namespace App\Modules\Simulation\Core\Events\Contracts;

interface SimulationEventInterface
{
    /**
     * Get the ID of the universe where the event occurred.
     */
    public function getUniverseId(): int;

    /**
     * Get the type of the simulation event (e.g. 'actor_died').
     */
    public function getType(): string;

    /**
     * Get the tick at which the event occurred.
     */
    public function getTick(): int;

    /**
     * Get the payload/metadata associated with the event.
     */
    public function getPayload(): array;
}
