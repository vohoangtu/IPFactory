<?php

namespace App\Modules\Intelligence\Actions;

use App\Contracts\UniverseEvaluatorInterface;
use App\Modules\Simulation\Models\UniverseSnapshot;

class EvaluateUniverseAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected UniverseEvaluatorInterface $evaluator
    ) {}

    /**
     * Tách logic Evaluate từ DecisionEngine hoặc RuntimeService cũ
     */
    public function execute(UniverseSnapshot $snapshot): array
    {
        return $this->evaluator->evaluate($snapshot);
    }
}

