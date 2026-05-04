<?php

namespace App\Modules\Simulation\Core\Runtime\Stages;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Runtime\Contracts\SimulationStageInterface;
use App\Modules\Simulation\Core\Engines\Physics\CosmicPressureEngine;

/**
 * PhysicsStage – high-level universal physics and pressures.
 */
final class PhysicsStage implements SimulationStageInterface
{
    public function __construct(
        protected \App\Modules\Simulation\Core\Engines\Physics\CosmicPressureEngine $cosmicPressureEngine,
        protected \App\Modules\Simulation\Core\Runtime\State\StateManager $stateManager
    ) {}

    public function run(\App\Modules\World\Models\Universe $universe, int $tick, ?\App\Modules\Simulation\Models\UniverseSnapshot $savedSnapshot = null, array $context = []): void
    {
        $state = $this->stateManager->get();
        if (!$state) {
            return;
        }

        $ctx = new \App\Modules\Simulation\Core\Domain\TickContext((int) ($universe->id ?? 0), $tick, (int) ($universe->seed ?? 0));
        $this->cosmicPressureEngine->handle($state, $ctx);
    }
}


