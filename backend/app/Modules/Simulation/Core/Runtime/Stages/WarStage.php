<?php

namespace App\Modules\Simulation\Core\Runtime\Stages;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Runtime\Contracts\SimulationStageInterface;
use App\Modules\Simulation\Core\Engines\Social\WarEngine;
use App\Modules\Simulation\Core\Domain\TickContext;

/**
 * War stage (Tier 12). Interval typically 50 ticks.
 */
final class WarStage implements SimulationStageInterface
{
    public function __construct(
        protected WarEngine $warEngine,
        protected \App\Modules\Simulation\Core\Runtime\State\StateManager $stateManager
    ) {}

    public function run(Universe $universe, int $tick, ?UniverseSnapshot $savedSnapshot = null, array $context = []): void
    {
        $state = $this->stateManager->get();
        if (!$state) return;

        $ctx = new TickContext((int) ($universe->id ?? 0), $tick, (int) ($universe->multiverse_id ?? 0));

        $this->warEngine->handle($state, $ctx);
    }
}


