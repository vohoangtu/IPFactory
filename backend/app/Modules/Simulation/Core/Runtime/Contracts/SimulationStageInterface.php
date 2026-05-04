<?php

namespace App\Modules\Simulation\Core\Runtime\Contracts;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;

interface SimulationStageInterface
{
    /**
     * Run this stage for the given universe at the given tick.
     * Optional saved snapshot (may be virtual) for stages that need snapshot context.
     * Optional context (e.g. engine response with _ticks) for stages that need it.
     *
     * @param  array<string, mixed>  $context  Optional: engine response, _ticks, etc.
     */
    public function run(Universe $universe, int $tick, ?UniverseSnapshot $savedSnapshot = null, array $context = []): void;
}

