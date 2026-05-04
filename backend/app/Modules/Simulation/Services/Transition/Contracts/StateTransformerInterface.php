<?php

namespace App\Modules\Simulation\Services\Transition\Contracts;

use App\Modules\Simulation\Core\Runtime\State\WorldState;

interface StateTransformerInterface
{
    /**
     * Apply a transformation to the world state during a power system transition.
     */
    public function apply(WorldState $state, string $targetPowerSystem): WorldState;
}

interface InvariantGuardInterface
{
    /**
     * Verify that a specific system invariant still holds after a transformation step.
     * Throws exception or triggers a corrective action if violated.
     */
    public function verify(WorldState $originalState, WorldState $newState): void;
}
