<?php

namespace App\Modules\Simulation\Core\Effects;

use App\Modules\Simulation\Core\Contracts\Effect;
use App\Modules\Simulation\Core\Runtime\State\WorldStateMutable;

/**
 * Merges updated world_rules into state_vector (Tier 2 mutable rules).
 */
final class WorldRulesUpdateEffect implements Effect
{
    /** @param array<string, mixed> $rules key => value to merge into state_vector.world_rules */
    public function __construct(
        private readonly array $rules,
    ) {
    }

    public function apply(WorldStateMutable $state): void
    {
        $vec = $state->getStateVector();
        $current = $vec['world_rules'] ?? [];
        if (!is_array($current)) {
            $current = [];
        }
        $vec['world_rules'] = array_merge($current, $this->rules);
        $state->setStateVector($vec);
    }

    public function getRules(): array
    {
        return $this->rules;
    }
}
