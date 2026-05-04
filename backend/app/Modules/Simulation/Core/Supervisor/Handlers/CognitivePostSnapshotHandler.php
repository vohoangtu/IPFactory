<?php

namespace App\Modules\Simulation\Core\Supervisor\Handlers;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Supervisor\Contracts\PostSnapshotHandlerInterface;
use App\Modules\Simulation\Services\Society\ActorCognitiveService;

final class CognitivePostSnapshotHandler implements PostSnapshotHandlerInterface
{
    public function __construct(
        private readonly ActorCognitiveService $cognitiveService,
    ) {}

    public function handle(Universe $universe, UniverseSnapshot $snapshot): void
    {
        // Skip when Rust is authoritative and cognitive fields already present
        if (config('worldos_simulation.simulation.rust_authoritative', true)) {
            $sv = is_array($snapshot->state_vector) ? $snapshot->state_vector : [];
            if (isset($sv['cognitive_aggregate'])) {
                return;
            }
        }

        $this->cognitiveService->computeAndStore($universe, $snapshot);
    }
}


