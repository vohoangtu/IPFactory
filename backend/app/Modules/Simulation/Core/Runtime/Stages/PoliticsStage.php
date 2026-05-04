<?php

namespace App\Modules\Simulation\Core\Runtime\Stages;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Runtime\Contracts\SimulationStageInterface;
use App\Modules\Simulation\Core\Engines\Social\PoliticsEngine;
use App\Modules\Simulation\Core\Engines\Social\LegitimacyEliteEngine;
use App\Modules\Simulation\Core\Domain\TickContext;

/**
 * Politics stage (Tier 11). Interval typically 20 ticks. Doc §17: legitimacy_aggregate, elite_ratio.
 */
final class PoliticsStage implements SimulationStageInterface
{
    public function __construct(
        protected PoliticsEngine $politicsEngine,
        protected LegitimacyEliteEngine $legitimacyEliteEngine,
        protected \App\Modules\Simulation\Core\Runtime\State\StateManager $stateManager
    ) {}

    public function run(Universe $universe, int $tick, ?UniverseSnapshot $savedSnapshot = null, array $context = []): void
    {
        $state = $this->stateManager->get();
        if (!$state) return;

        $ctx = new TickContext((int) ($universe->id ?? 0), $tick, (int) ($universe->multiverse_id ?? 0));

        $this->politicsEngine->handle($state, $ctx);
        $this->legitimacyEliteEngine->handle($state, $ctx);
    }
}


