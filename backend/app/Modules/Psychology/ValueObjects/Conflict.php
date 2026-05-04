<?php

namespace App\Modules\Psychology\ValueObjects;

/**
 * Conflict – tension between two opposing Impulses.
 *
 * ConflictResolver uses tension to determine how much each impulse
 * suppresses the other (vector-sum model, NOT winner-take-all).
 */
final class Conflict
{
    public function __construct(
        public readonly Impulse $impulseA,
        public readonly Impulse $impulseB,
        public readonly float   $tension, // intensityA * intensityB
    ) {}

    public function stressContribution(): float
    {
        return $this->tension * 0.2;
    }

    public function toArray(): array
    {
        return [
            'impulse_a' => $this->impulseA->toArray(),
            'impulse_b' => $this->impulseB->toArray(),
            'tension'   => $this->tension,
            'stress'    => $this->stressContribution(),
        ];
    }
}
