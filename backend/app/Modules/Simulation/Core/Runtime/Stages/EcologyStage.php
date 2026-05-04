<?php

namespace App\Modules\Simulation\Core\Runtime\Stages;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Runtime\Contracts\SimulationStageInterface;
use App\Modules\Simulation\Core\Engines\Biological\EcologicalCollapseEngine;
use App\Modules\Simulation\Core\Engines\Physics\ClimateEngine;
use App\Modules\Simulation\Core\Engines\Biological\EcologicalPhaseTransitionEngine;
use App\Modules\Simulation\Core\Engines\Physics\GeologicalEngine;
use App\Modules\Simulation\Core\Domain\TickContext;

/**
 * Ecology stage: collapse, climate, phase transition, geology.
 */
final class EcologyStage implements SimulationStageInterface
{
    public function __construct(
        protected EcologicalCollapseEngine $ecologicalCollapseEngine,
        protected ClimateEngine $planetaryClimateEngine,
        protected EcologicalPhaseTransitionEngine $ecologicalPhaseTransitionEngine,
        protected GeologicalEngine $geologicalEngine,
        protected \App\Modules\Simulation\Core\Runtime\State\StateManager $stateManager
    ) {}

    public function run(Universe $universe, int $tick, ?UniverseSnapshot $savedSnapshot = null, array $context = []): void
    {
        $state = $this->stateManager->get();
        if (!$state) {
            return;
        }

        $ctx = new TickContext((int) ($universe->id ?? 0), $tick, (int) ($universe->multiverse_id ?? 0));

        // 1. Collapse & Crisis
        $this->ecologicalCollapseEngine->handle($state, $ctx);

        // 2. Environment (Planetary Climate)
        $this->planetaryClimateEngine->handle($state, $ctx);

        // 3. Phase Transition (Forest, Grassland, Desert)
        $this->ecologicalPhaseTransitionEngine->handle($state, $ctx);

        // 4. Geological (Very slow)
        $this->geologicalEngine->handle($state, $ctx);
    }
}


