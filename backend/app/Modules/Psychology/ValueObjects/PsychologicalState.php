<?php

namespace App\Modules\Psychology\ValueObjects;

/**
 * Runtime emotional state – mutable, updated every tick.
 * CBT (Cognitive Behavioral Therapy) model: emotion evolves event-by-event,
 * and naturally decays back toward baseline over time.
 */
final class PsychologicalState
{
    public function __construct(
        public float $fear    = 0.0, // [0,1]
        public float $anger   = 0.0,
        public float $sadness = 0.0,
        public float $joy     = 0.0,
        public float $stress  = 0.0,
        public float $trust   = 0.5,
    ) {}

    /**
     * Apply emotion deltas from an event interpretation.
     * Values are clamped to [0,1] after application.
     *
     * @param array<string, float> $delta  e.g. ['fear' => 0.2, 'joy' => -0.1]
     */
    public function applyDelta(array $delta): void
    {
        foreach ($delta as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = (float) $this->$key + $value;
            }
        }
        $this->clamp();
    }

    /**
     * Emotional decay each tick – emotions return to baseline (CBT inertia model).
     * trust decays toward 0.5, others toward 0.
     *
     * @param float $rate  e.g. 0.95 means 5% decay per tick
     */
    public function decay(float $rate = 0.95): void
    {
        $this->fear    *= $rate;
        $this->anger   *= $rate;
        $this->sadness *= $rate;
        $this->joy     *= $rate;
        $this->stress  *= $rate;
        // trust decays toward 0.5
        $this->trust = 0.5 + ($this->trust - 0.5) * $rate;
        $this->clamp();
    }

    /**
     * Clamp all values to valid range.
     */
    public function clamp(): void
    {
        $this->fear    = max(0.0, min(1.0, $this->fear));
        $this->anger   = max(0.0, min(1.0, $this->anger));
        $this->sadness = max(0.0, min(1.0, $this->sadness));
        $this->joy     = max(0.0, min(1.0, $this->joy));
        $this->stress  = max(0.0, min(1.0, $this->stress));
        $this->trust   = max(0.0, min(1.0, $this->trust));
    }

    public function toArray(): array
    {
        return [
            'fear'    => $this->fear,
            'anger'   => $this->anger,
            'sadness' => $this->sadness,
            'joy'     => $this->joy,
            'stress'  => $this->stress,
            'trust'   => $this->trust,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            fear:    (float) ($data['fear']    ?? 0.0),
            anger:   (float) ($data['anger']   ?? 0.0),
            sadness: (float) ($data['sadness'] ?? 0.0),
            joy:     (float) ($data['joy']     ?? 0.0),
            stress:  (float) ($data['stress']  ?? 0.0),
            trust:   (float) ($data['trust']   ?? 0.5),
        );
    }

    public static function baseline(): self
    {
        return new self();
    }
}
