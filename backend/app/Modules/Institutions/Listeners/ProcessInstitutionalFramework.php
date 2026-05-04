<?php

namespace App\Modules\Institutions\Listeners;

use App\Modules\Simulation\Events\UniverseSimulationPulsed;
use App\Modules\Institutions\Services\InstitutionEvolutionService;
use App\Modules\Institutions\Services\DiplomaticResonanceEngine;

class ProcessInstitutionalFramework
{
    public function __construct(
        private InstitutionEvolutionService $evolutionService,
        private DiplomaticResonanceEngine $diplomacyEngine,
        private \App\Modules\Institutions\Services\SupremeEntityEvolutionService $supremeEntityService,
        private \App\Modules\Institutions\Services\SocialDynamicsEngine $socialDynamicsEngine
    ) {}

    public function handle(UniverseSimulationPulsed $event): void
    {
        // 1. Process Institution Evolution (Influence, Spawning, Collapse)
        $this->evolutionService->processPulse($event->universe, $event->snapshot);

        // 2. Process Diplomatic resonance (Civilization relationships)
        $this->diplomacyEngine->updateRelationships($event->universe, $event->snapshot);

        // 3. Process Supreme Entities (Ascension, Cosmic Impact)
        $this->supremeEntityService->processPulse($event->universe, $event->snapshot);

        // 4. Social & Cultural Dynamics
        $this->socialDynamicsEngine->advance($event->universe, (int)$event->snapshot->tick);
    }
}

