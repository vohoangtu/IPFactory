<?php

namespace App\Modules\Simulation\Services\Core;

use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use App\Modules\Simulation\Services\Meta\UniverseRuntimeService;
use Illuminate\Support\Facades\Log;

class ImplicitOrchestratorService
{
    public function __construct(
        protected \App\Modules\Simulation\Core\Domain\Pipelines\SpawnPipeline $spawnPipeline
    ) {}

    /**
     * Spawn a new universe for a world (optionally forked from parent).
     */
    public function spawnUniverse(\App\Modules\World\Models\World $world, ?int $parentUniverseId = null, ?int $sagaId = null, ?array $branchPayload = null): \App\Modules\World\Models\Universe
    {
        return $this->spawnPipeline->run($world, $parentUniverseId, $sagaId, $branchPayload);
    }

    /**
     * Ensure a saga exists for the universe context.
     * Returns a mock object if model is missing to prevent crash.
     */
    public function ensureSaga(\App\Modules\World\Models\Universe $universe): object
    {
        $sagaId = $universe->saga_id ?? 1; // Default to 1 or logic
        return (object)['id' => $sagaId];
    }

    /**
     * Advance universes in batch for a given World.
     * @return array<int, array> Map of universe_id => response
     */
    public function runBatch(\App\Modules\World\Models\World $world, int $ticks): array
    {
        $advanceAction = app(\App\Modules\Simulation\Actions\AdvanceSimulationAction::class);
        $universes = $world->universes()->where('status', 'active')->get();
        $results = [];

        foreach ($universes as $universe) {
            $results[$universe->id] = $advanceAction->execute($universe->id, $ticks);
        }

        return $results;
    }
}



