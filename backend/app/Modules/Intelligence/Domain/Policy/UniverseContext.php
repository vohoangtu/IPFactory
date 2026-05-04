<?php

namespace App\Modules\Intelligence\Domain\Policy;

/**
 * Immutable snapshot of global universe state passed into the DecisionEngine.
 * Pure domain concept — no Eloquent.
 */
final class UniverseContext
{
    public function __construct(
        public readonly float $entropy,
        public readonly float $stabilityIndex,
        public readonly float $mythIntensity,
        public readonly float $contractDensity,
        public readonly int   $tick,
    ) {}

    public static function fromStateVector(array $vec, int $tick): self
    {
        // Support both 'entropy' (new DDD format) and 'global_entropy' (legacy state_vector)
        $entropy = (float) ($vec['entropy'] ?? $vec['global_entropy'] ?? 0.0);

        return new self(
            entropy:         $entropy,
            stabilityIndex:  (float) ($vec['stability_index'] ?? $vec['structural_coherence'] ?? 1.0),
            mythIntensity:   (float) ($vec['metrics']['myth_intensity'] ?? $vec['myth_intensity'] ?? 0.0),
            contractDensity: (float) ($vec['metrics']['contract_density'] ?? $vec['contract_density'] ?? 0.0),
            tick:            $tick,
        );
    }

    /** Returns 1.0 when in crisis (high entropy + low stability). */
    public function crisisIndex(): float
    {
        return ($this->entropy + (1.0 - $this->stabilityIndex)) / 2.0;
    }
}
