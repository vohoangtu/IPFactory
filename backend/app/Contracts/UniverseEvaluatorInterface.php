<?php

namespace App\Contracts;

use App\Modules\Simulation\Models\UniverseSnapshot;

interface UniverseEvaluatorInterface
{
    /**
     * Evaluate snapshot and return recommendation (IP-score, fork/continue/archive, mutation suggestion).
     *
     * @return array{ip_score: float, recommendation: string, mutation_suggestion: array|null}
     */
    public function evaluate(UniverseSnapshot $snapshot): array;
}

