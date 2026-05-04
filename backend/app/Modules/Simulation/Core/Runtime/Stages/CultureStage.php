<?php

namespace App\Modules\Simulation\Core\Runtime\Stages;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Runtime\Contracts\SimulationStageInterface;
use App\Modules\Intelligence\Services\CultureEngine;

/**
 * Culture stage: meme transmission, drift, culture_group (Tier 7). Feeds behavior.
 */
final class CultureStage implements SimulationStageInterface
{
    public function __construct(
        protected CultureEngine $cultureEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\MythogenesisEngine $mythogenesisEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\MeaningEngine $meaningEngine,
        protected \App\Modules\Simulation\Core\Engines\Meta\KnowledgeEvolutionEngine $knowledgeEvolutionEngine,
        protected \App\Modules\Simulation\Core\Runtime\State\StateManager $stateManager
    ) {}

    public function run(Universe $universe, int $tick, ?UniverseSnapshot $savedSnapshot = null, array $context = []): void
    {
        $state = $this->stateManager->get();
        if (!$state) return;

        $ctx = new \App\Modules\Simulation\Core\Domain\TickContext((int) ($universe->id ?? 0), $tick, (int) ($universe->seed ?? 0));

        $this->cultureEngine->runWithState($state, $tick);
        $this->mythogenesisEngine->handle($state, $ctx);
        $this->meaningEngine->handle($state, $ctx);
        $this->knowledgeEvolutionEngine->handle($state, $ctx);
    }
}


