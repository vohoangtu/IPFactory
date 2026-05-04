<?php

namespace App\Modules\Simulation\Core\Effects;

use App\Modules\Simulation\Core\Contracts\Effect;
use App\Modules\Simulation\Core\Runtime\State\WorldStateMutable;

/**
 * Writes updated zone array (with computed pressures in state) back into WorldStateMutable.
 * Used by PotentialFieldEngine after compute → decay → diffuse.
 */
final class ZoneFieldUpdateEffect implements Effect
{
    /** @param array<int, array<string, mixed>> $zones zones with state.war_pressure, etc. */
    public function __construct(
        private readonly array $zones,
    ) {
    }

    public function apply(WorldStateMutable $state): void
    {
        $state->setZones($this->zones);
    }
}
