<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Modules\Simulation\Models\UniverseSnapshot;

interface DecisionEngineInterface
{
    /**
     * Decide action for a universe snapshot.
     *
     * @return array{action: string, meta: array, navigator_score: float}
     */
    public function decide(UniverseSnapshot $snapshot): array;

    /**
     * Compute novelty + complexity + divergence navigator score.
     */
    public function computeNavigatorScore(UniverseSnapshot $snapshot): array;
}
