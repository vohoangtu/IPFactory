<?php

namespace App\Modules\Intelligence\Actions;

use App\Modules\Simulation\Models\UniverseSnapshot;

class DecideUniverseAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected \App\Contracts\DecisionEngineInterface $decisionEngine
    ) {}

    /**
     * Thay thế DecisionEngine cũ
     * 
     * @return array{action: string, meta: array}
     */
    public function execute(UniverseSnapshot $snapshot): array
    {
        return $this->decisionEngine->decide($snapshot);
    }
}


