<?php

namespace App\Modules\Intelligence\Listeners;

use App\Modules\Simulation\Events\UniverseSimulationPulsed;
use App\Modules\Intelligence\Actions\SpawnFromEventsAction;

class ProcessIntelligenceEvolution
{
    public function __construct(
        private SpawnFromEventsAction $spawnAction,
        private \App\Modules\Intelligence\Services\ActorEvolutionService $evolutionService,
        private \App\Modules\Intelligence\Services\AgentAutonomyService $autonomyService
    ) {}

    public function handle(UniverseSimulationPulsed $event): void
    {
        $tick = (int) $event->snapshot->tick;
        
        // 1. Spawn logic
        $this->spawnAction->handle($event->universe, $tick);

        // 2. Evolution
        $this->evolutionService->evolve($event->universe, $tick);

        // 3. Autonomy Decisions
        $this->autonomyService->process($event->universe, $tick);

        // 4. Survival: chạy trong AdvanceSimulationAction (cùng process) để đảm bảo lưu DB, không gọi lại ở đây
    }
}

