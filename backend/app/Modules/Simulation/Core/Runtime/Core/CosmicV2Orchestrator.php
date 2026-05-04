<?php

namespace App\Modules\Simulation\Core\Runtime\Core;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Runtime\Domain\UniverseState;
use App\Modules\Institutions\Services\CivilizationComplexityEngine;
use App\Modules\Institutions\Services\EntropyEngine;
use App\Modules\Institutions\Services\StabilityEngine;
use App\Modules\Institutions\Services\AscensionEngine;

class CosmicV2Orchestrator
{
    public function __construct(
        protected CivilizationComplexityEngine $complexityEngine,
        protected EntropyEngine $entropyEngine,
        protected StabilityEngine $stabilityEngine,
        protected AscensionEngine $ascensionEngine,
        protected SimulationEventDispatcher $dispatcher
    ) {}

    public function run(Universe $universe, UniverseSnapshot $snapshot): void
    {
        // 1. Prepare State
        $state = UniverseState::fromModels($universe, $snapshot);

        // 2. Run Engines in sequence (Pure Logic)
        $state = $this->complexityEngine->evaluate($state);
        $state = $this->entropyEngine->evaluate($state);
        $state = $this->stabilityEngine->evaluate($state);
        
        $events = $this->ascensionEngine->evaluate($state);

        // 3. Persist updated state metrics back to models
        $this->syncStateToModels($state, $universe, $snapshot);

        // 4. Dispatch events to trigger mutations (Projectors)
        $this->dispatcher->dispatch($events, $universe);
    }

    protected function syncStateToModels(UniverseState $state, Universe $universe, UniverseSnapshot $snapshot): void
    {
        // Update Universe
        $universe->entropy = $state->entropy;
        // Optimization: only save if changed, but for simulation we often save anyway
        $universe->save();

        // Update Snapshot Metrics
        $metrics = $snapshot->metrics ?? [];
        $metrics['order'] = $state->order;
        $metrics['energy_level'] = $state->energyLevel;
        $metrics['civilization_complexity'] = $state->civilizationComplexity;
        $metrics['institution_strength'] = $state->institutionStrength;
        $metrics['information_density'] = $state->informationDensity;
        
        $snapshot->metrics = $metrics;
        
        // Update State Vector Pressures & Axioms
        $stateVector = $snapshot->state_vector ?? [];
        $stateVector['pressures'] = $state->pressures;
        $stateVector['axioms'] = $state->axioms;
        $snapshot->state_vector = $stateVector;

        $snapshot->save();
    }
}

