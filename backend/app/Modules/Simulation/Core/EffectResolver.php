<?php

namespace App\Modules\Simulation\Core;

use App\Modules\Simulation\Core\Contracts\Effect;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\State\WorldStateMutable;

/**
 * Applies a list of effects to a mutable copy of WorldState and returns the resulting WorldState.
 */
final class EffectResolver
{
    /**
     * @param Effect[] $effects
     */
    public function resolve(WorldState $state, array $effects): WorldState
    {
        $mutable = WorldStateMutable::fromWorldState($state);
        foreach ($effects as $effect) {
            $effect->apply($mutable);
        }
        return $mutable->toWorldState();
    }
}
