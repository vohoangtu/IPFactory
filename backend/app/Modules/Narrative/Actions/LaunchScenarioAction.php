<?php

namespace App\Modules\Narrative\Actions;

use App\Modules\World\Models\Universe;
use App\Contracts\ScenarioEngineInterface;

class LaunchScenarioAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected ScenarioEngineInterface $scenarioEngine
    ) {}

    public function execute(Universe $universe, string $scenarioId): array
    {
        return $this->scenarioEngine->launch($universe, $scenarioId);
    }
}

