<?php

namespace App\Modules\Intelligence\Services\Consciousness;

/**
 * The Civilization's internal model of its own identity and state.
 * Layer 9: Self-Modeling.
 */
class SelfModel
{
    public function __construct(
        public readonly string $id,
        public readonly array $currentState,
        /** @var \App\Modules\Simulation\Models\CivilizationAttractor[] List of active rules */
        public readonly array $activeAttractors,
        /** @var string Primary goal/telos of this civilization */
        public readonly string $telos = 'Complexity Maximization'
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'current_state' => $this->currentState,
            'active_attractors' => array_map(fn($a) => $a->name, $this->activeAttractors),
            'telos' => $this->telos,
        ];
    }
}

