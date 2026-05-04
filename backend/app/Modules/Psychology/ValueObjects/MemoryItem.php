<?php

namespace App\Modules\Psychology\ValueObjects;

/**
 * MemoryItem – a single encoded memory from past events.
 *
 * Freudian model: high-intensity events become "trauma" memories that
 * persist with strong weight and bias future interpretations.
 * Ordinary memories decay naturally (weight decays each tick).
 */
final class MemoryItem
{
    public const TYPE_TRAUMA   = 'trauma';
    public const TYPE_REWARD   = 'reward';
    public const TYPE_BETRAYAL = 'betrayal';
    public const TYPE_SOCIAL   = 'social';
    public const TYPE_NEUTRAL  = 'neutral';

    public function __construct(
        public readonly string $type,
        public readonly float  $valence,        // [-1, 1]
        public readonly float  $intensity,      // [0, 1]
        public float           $weight,         // decays over time
        public readonly bool   $isTrauma,
        public readonly float  $traumaStrength, // 0 if not trauma
        public readonly int    $tick,           // simulation tick when formed
    ) {}

    /**
     * Decay weight over time. Returns new clone-like value (inline update).
     * Call each tick via MemoryStream::decayAll().
     */
    public function decay(float $rate = 0.97): void
    {
        // Trauma memories decay slower
        $effectiveRate = $this->isTrauma ? ($rate + (1.0 - $rate) * 0.5) : $rate;
        $this->weight = max(0.0, $this->weight * $effectiveRate);
    }

    /**
     * Effective contribution of this memory to interpretation bias.
     */
    public function effectiveBias(): float
    {
        return $this->valence * $this->weight * ($this->isTrauma ? 1.5 : 1.0);
    }

    public function toArray(): array
    {
        return [
            'type'           => $this->type,
            'valence'        => $this->valence,
            'intensity'      => $this->intensity,
            'weight'         => $this->weight,
            'is_trauma'      => $this->isTrauma,
            'trauma_strength'=> $this->traumaStrength,
            'tick'           => $this->tick,
        ];
    }

    public static function fromEvent(
        string $type,
        float  $valence,
        float  $intensity,
        int    $tick,
        bool   $forceTrauma = false,
    ): self {
        $isTrauma      = $forceTrauma || $intensity > 0.8;
        $traumaStrength = $isTrauma ? $intensity : 0.0;

        return new self(
            type:          $type,
            valence:       $valence,
            intensity:     $intensity,
            weight:        1.0,
            isTrauma:      $isTrauma,
            traumaStrength: $traumaStrength,
            tick:          $tick,
        );
    }
}
