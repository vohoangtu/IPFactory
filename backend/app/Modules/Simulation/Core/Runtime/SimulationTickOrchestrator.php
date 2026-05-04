<?php

namespace App\Modules\Simulation\Core\Runtime;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;

/**
 * Orchestrates the tick pipeline after engine advance + state sync.
 * AdvanceSimulationAction calls this instead of invoking each engine directly.
 */
final class SimulationTickOrchestrator
{
    public function __construct(
        protected SimulationTickPipeline $pipeline
    ) {}

    /**
     * Run the full tick pipeline for the given universe at the given tick.
     * Call after: engine advance, ensureEntropyFloor, ensureStateVectorHasZones,
     * temporalSync, syncUniverseFromSnapshotData, snapshot save/virtual, event fired.
     *
     * @param  array<string, mixed>  $engineResponse  Raw response from engine->advance() (and _ticks) for stages
     */
    public function run(
        Universe $universe,
        int $tick,
        ?UniverseSnapshot $savedSnapshot = null,
        array $engineResponse = []
    ): void {
        $this->pipeline->run($universe, $tick, $savedSnapshot, $engineResponse);
    }

    /** @return array<int, mixed> */
    public function getLastEngineEvents(): array
    {
        return $this->pipeline->getLastEngineEvents();
    }
}

