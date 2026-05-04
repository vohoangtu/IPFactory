<?php

namespace App\Modules\Intelligence\Domain\Phase;

/**
 * Value Object representing the distribution of macro-phases in the civilization.
 * All properties are floats between 0.0 and 1.0.
 */
final class PhaseScore
{
    public function __construct(
        public readonly float $primitive,
        public readonly float $feudal,
        public readonly float $industrial,
        public readonly float $information,
        public readonly float $fragmented
    ) {}

    public function toArray(): array
    {
        return [
            'primitive' => $this->primitive,
            'feudal' => $this->feudal,
            'industrial' => $this->industrial,
            'information' => $this->information,
            'fragmented' => $this->fragmented,
        ];
    }
}
