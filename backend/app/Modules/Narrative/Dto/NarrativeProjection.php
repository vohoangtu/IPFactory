<?php

namespace App\Modules\Narrative\Dto;

/**
 * NarrativeProjection: A simplified "perceptual" snapshot of the simulation state.
 * This is what the LLM actually "sees" to make interpretations.
 */
class NarrativeProjection
{
    public function __construct(
        public readonly float $entropy,
        public readonly float $stability,
        public readonly array $dominantForces = [],
        public readonly array $activeConflicts = [],
        public readonly array $metrics = []
    ) {}

    /**
     * Convert the numeric state into descriptive "tokens" for the LLM.
     */
    public function toNarrativeTokens(): array
    {
        $tokens = [];

        if ($this->entropy > 0.8) $tokens[] = "CHAOS_ABSOLUTE";
        elseif ($this->entropy > 0.5) $tokens[] = "RISING_UNREST";
        
        if ($this->stability < 0.2) $tokens[] = "COLLAPSE_IMMINENT";
        elseif ($this->stability > 0.8) $tokens[] = "ERA_OF_ORDER";

        return array_merge($tokens, $this->activeConflicts);
    }
}
