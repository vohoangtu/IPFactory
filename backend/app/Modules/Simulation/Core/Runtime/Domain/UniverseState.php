<?php

namespace App\Modules\Simulation\Core\Runtime\Domain;

use App\Modules\World\Models\Universe;

/**
 * Immutable State of the Universe at a specific tick.
 * Used as input for V2 Engines.
 */
class UniverseState
{
    public function __construct(
        public readonly int $tick,
        public readonly int $seed,
        public float $entropy,
        public float $order,
        public float $energyLevel,
        public int $epoch,
        public int $level,
        public int $civilizationCount = 0,
        public float $civilizationComplexity = 0,
        public float $institutionStrength = 0,
        public float $informationDensity = 0,
        public array $pressures = [],
        public array $axioms = []
    ) {}

    /**
     * Create state from Universe and Snapshot models.
     */
    public static function fromModels(\App\Modules\World\Models\Universe $universe, \App\Modules\Simulation\Models\UniverseSnapshot $snapshot): self
    {
        $metrics = $snapshot->metrics ?? [];
        $stateVector = $snapshot->state_vector ?? [];

        return new self(
            tick: $snapshot->tick,
            seed: $universe->seed ?? 0,
            entropy: (float) ($snapshot->entropy ?? 0.5),
            order: (float) ($metrics['order'] ?? 0.5),
            energyLevel: (float) ($metrics['energy_level'] ?? 0.5),
            epoch: $universe->epoch ?? 1,
            level: $universe->level ?? 1,
            civilizationCount: (int) ($metrics['civilization_count'] ?? 0),
            civilizationComplexity: (float) ($metrics['civilization_complexity'] ?? 0),
            institutionStrength: (float) ($metrics['institution_strength'] ?? 0),
            informationDensity: (float) ($metrics['information_density'] ?? 0),
            pressures: $stateVector['pressures'] ?? [],
            axioms: $stateVector['axioms'] ?? []
        );
    }
}

