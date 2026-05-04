<?php

namespace App\Modules\Simulation\Core\Effects;

use App\Modules\Simulation\Core\Contracts\Effect;
use App\Modules\Simulation\Core\Runtime\State\WorldStateMutable;

/**
 * Phase 5: General purpose state vector update effect.
 * Replaces direct $state->set() calls in Pure Engines.
 */
final class WorldStateUpdateEffect implements Effect
{
    /**
     * @param array<string, mixed> $changes Key-value pairs where keys can use dot-notation.
     */
    public function __construct(
        private readonly array $changes,
    ) {
    }

    public function apply(WorldStateMutable $state): void
    {
        foreach ($this->changes as $key => $value) {
            $state->set($key, $value);
        }
    }
}
