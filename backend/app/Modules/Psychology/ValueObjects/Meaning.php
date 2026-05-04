<?php

namespace App\Modules\Psychology\ValueObjects;

/**
 * Meaning – the subjective interpretation of an event by a specific actor.
 *
 * Core of the Psychology Layer: the SAME event produces DIFFERENT meanings
 * depending on the actor's TraitVector, MemoryStream and social context.
 * (Freud's subjective perception; Cognitive Appraisal theory by Lazarus)
 */
final class Meaning
{
    public const TYPE_THREAT      = 'threat';
    public const TYPE_SUPPORT     = 'support';
    public const TYPE_REJECTION   = 'rejection';
    public const TYPE_OPPORTUNITY = 'opportunity';
    public const TYPE_NEUTRAL     = 'neutral';
    public const TYPE_INSULT      = 'insult';

    public function __construct(
        public readonly string $type,
        public readonly float  $valence,   // [-1, 1]  negative = bad
        public readonly float  $intensity, // [0, 1]   strength of meaning
        public readonly float  $certainty, // [0, 1]   how sure actor is
        /** @var string[] */
        public readonly array  $tags = [],
    ) {}

    public function threatLevel(): float
    {
        return max(0.0, -$this->valence) * $this->intensity;
    }

    public function isSocialThreat(): bool
    {
        return in_array('social_threat', $this->tags, true);
    }

    public function selfImpact(): float
    {
        // Negative valence → negative self-impact (ego hit)
        return $this->valence * $this->intensity;
    }

    /**
     * Merge two Meanings (bias overlay).
     * Used in MeaningEngine to combine base + bias layers.
     */
    public function withAdjustment(float $valenceDelta, float $intensityDelta = 0.0): self
    {
        return new self(
            type:      $this->type,
            valence:   max(-1.0, min(1.0, $this->valence + $valenceDelta)),
            intensity: max(0.0, min(1.0, $this->intensity + $intensityDelta)),
            certainty: $this->certainty,
            tags:      $this->tags,
        );
    }

    public function toArray(): array
    {
        return [
            'type'         => $this->type,
            'valence'      => $this->valence,
            'intensity'    => $this->intensity,
            'certainty'    => $this->certainty,
            'threat_level' => $this->threatLevel(),
            'self_impact'  => $this->selfImpact(),
            'tags'         => $this->tags,
        ];
    }

    public static function neutral(): self
    {
        return new self(self::TYPE_NEUTRAL, 0.0, 0.1, 0.5);
    }
}
