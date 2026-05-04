<?php

namespace App\Modules\Simulation\Core\Runtime\Stages;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Runtime\Contracts\SimulationStageInterface;
use App\Modules\Simulation\Core\Engines\Social\PopulationEngine;
use App\Modules\Simulation\Core\Engines\Social\AgricultureEngine;
use App\Modules\Simulation\Core\Engines\Social\DiseaseEngine;
use App\Modules\Simulation\Core\Domain\TickContext;

/**
 * PopulationStage – handles biological life cycles.
 */
final class PopulationStage implements SimulationStageInterface
{
    public function __construct(
        protected PopulationEngine $populationEngine,
        protected AgricultureEngine $agricultureEngine,
        protected DiseaseEngine $diseaseEngine,
        protected \App\Modules\Simulation\Core\Runtime\State\StateManager $stateManager
    ) {}

    public function run(Universe $universe, int $tick, ?UniverseSnapshot $savedSnapshot = null, array $context = []): void
    {
        $state = $this->stateManager->get();
        if (!$state) return;

        $ctx = new TickContext((int) ($universe->id ?? 0), $tick, (int) ($universe->multiverse_id ?? 0));

        // 1. Bio-layer computation (standardized state)
        $this->populationEngine->handle($state, $ctx);
        $this->agricultureEngine->handle($state, $ctx);
        $this->diseaseEngine->handle($state, $ctx);
    }
}


