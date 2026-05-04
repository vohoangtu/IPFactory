<?php

namespace App\Modules\Simulation\Core\Runtime\Stages;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Runtime\Contracts\SimulationStageInterface;
use App\Modules\Simulation\Core\Engines\Social\CivilizationFieldEngine;
use App\Modules\Simulation\Core\Engines\Social\CivilizationLongCycleEngine;

/**
 * CivilizationFieldStage – handles CFT (Civilization Field Theory).
 */
final class CivilizationFieldStage implements SimulationStageInterface
{
    public function __construct(
        protected CivilizationFieldEngine $fieldEngine,
        protected CivilizationLongCycleEngine $cycleEngine,
        protected \App\Modules\Institutions\Services\SocialDynamicsEngine $socialEngine,
        protected \App\Modules\Simulation\Core\Runtime\State\StateManager $stateManager
    ) {}

    public function run(Universe $universe, int $tick, ?UniverseSnapshot $savedSnapshot = null, array $context = []): void
    {
        $state = $this->stateManager->get();
        if (!$state) return;

        // 1. Compute and update fields (Phase 44 Unified)
        $this->fieldEngine->runWithState($state, $tick);
        
        // 2. Compute social dynamics & ethos (Phase 44 Unified)
        $this->socialEngine->runWithState($state, $tick);

        // 3. Compute historical cycle (Still needs universe for now)
        if ($savedSnapshot) {
            $cycle = $this->cycleEngine->compute($universe, $savedSnapshot);
            $state->set('cycle', $cycle);
        }
    }
}


