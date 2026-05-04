<?php

namespace App\Modules\Simulation\Core\Effects;

use App\Modules\Simulation\Core\Contracts\Effect;
use App\Modules\Simulation\Core\Runtime\State\WorldStateMutable;

/**
 * Updates neighbor lists for specific zones (adaptive topology).
 * Payload: zone index => array of neighbor ids. Only merges into existing zones.
 */
final class ZoneNeighborsUpdateEffect implements Effect
{
    /** @param array<int, array<int|string>> $neighborsByIndex zone index => list of neighbor ids */
    public function __construct(
        private readonly array $neighborsByIndex,
    ) {
    }

    public function apply(WorldStateMutable $state): void
    {
        $zones = $state->getZones();
        foreach ($this->neighborsByIndex as $index => $neighborIds) {
            if (!isset($zones[$index]) || !is_array($neighborIds)) {
                continue;
            }
            $zones[$index]['neighbors'] = array_values(array_unique($neighborIds));
        }
        $state->setZones($zones);
    }
}
