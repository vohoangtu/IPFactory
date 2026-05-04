<?php

namespace App\Modules\Narrative\Dto;

/**
 * NarrativeMeaning: The structured output from the LLM Interpretation phase.
 * Represents the "story" meaning without calculated system impacts.
 */
class NarrativeMeaning
{
    public function __construct(
        public readonly string $summary,
        public readonly string $tension, // low, medium, high
        public readonly string $direction, // growth, stagnation, collapse
        public readonly array $keyFactors = [],
        public readonly array $omens = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            summary: $data['summary'] ?? ($data['chronicle'] ?? 'No summary provided.'),
            tension: $data['tension'] ?? 'medium',
            direction: $data['direction'] ?? 'stagnation',
            keyFactors: $data['key_factors'] ?? ($data['events'] ?? []),
            omens: $data['omens'] ?? []
        );
    }
}
