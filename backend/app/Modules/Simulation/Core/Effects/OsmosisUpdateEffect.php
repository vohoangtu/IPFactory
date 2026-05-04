<?php

namespace App\Modules\Simulation\Core\Effects;

use App\Modules\Simulation\Core\Contracts\Effect;
use App\Modules\Simulation\Core\Runtime\State\WorldStateMutable;

/**
 * Applies "Reality Bleeding" results from MultiverseOsmosisEngine.
 * Adjusts innovation, spirituality, myth, and entropy in the state vector.
 */
final class OsmosisUpdateEffect implements Effect
{
    public function __construct(
        private readonly array $bleed,
    ) {
    }

    public function apply(WorldStateMutable $state): void
    {
        $vec = $state->getStateVector();

        // 1. Innovation Gain
        if (($this->bleed['innovation_gain'] ?? 0) > 0) {
            $current = (float) ($vec['innovation_metrics']['total_score'] ?? 0.0);
            $vec['innovation_metrics']['total_score'] = min(1.0, $current + $this->bleed['innovation_gain']);
        }

        // 2. Spirituality Gain
        if (($this->bleed['spirituality_gain'] ?? 0) > 0) {
            $current = (float) ($vec['fields']['spirituality'] ?? 0.0);
            $vec['fields']['spirituality'] = min(1.0, $current + $this->bleed['spirituality_gain']);
        }

        // 3. Myth Gain
        if (($this->bleed['myth_gain'] ?? 0) > 0) {
            $current = (float) ($vec['myth'] ?? 0.0);
            $vec['myth'] = min(1.0, $current + $this->bleed['myth_gain']);
        }

        // 4. Entropy Gain (Harmful leakage)
        if (($this->bleed['entropy_gain'] ?? 0) > 0) {
            $current = (float) $state->getEntropy();
            $state->setEntropy(min(1.0, $current + $this->bleed['entropy_gain']));
        }

        $state->setStateVector($vec);
    }
}
