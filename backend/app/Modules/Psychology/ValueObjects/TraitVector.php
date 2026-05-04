<?php

namespace App\Modules\Psychology\ValueObjects;

/**
 * Big Five (OCEAN) personality baseline.
 * Immutable – set when actor is created, biases probability (does NOT decide behavior).
 *
 * Mapping:
 * - neuroticism      → amplifies negative Meaning (Freud bias layer)
 * - extraversion     → boosts social approach impulses
 * - openness         → boosts curiosity / explore behaviors
 * - agreeableness    → boosts cooperate, damps resist/attack
 * - conscientiousness → boosts duty impulses
 */
final class TraitVector
{
    // Indices based on Rust worldos-core/src/agent.rs
    public const DOMINANCE = 0;
    public const AMBITION = 1;
    public const COERCION = 2;
    public const LOYALTY = 3;
    public const EMPATHY = 4;
    public const SOLIDARITY = 5;
    public const CONFORMITY = 6;
    public const PRAGMATISM = 7;
    public const CURIOSITY = 8;
    public const DOGMATISM = 9;
    public const RISK_TOLERANCE = 10;
    public const FEAR = 11;
    public const VENGEANCE = 12;
    public const HOPE = 13;
    public const GRIEF = 14;
    public const PRIDE = 15;
    public const SHAME = 16;

    /** @var float[] */
    private array $traits;

    public function __construct(array $traits = [])
    {
        $this->traits = array_pad(array_slice($traits, 0, 17), 17, 0.5);
    }

    public static function fromOcean(
        float $openness = 0.5,
        float $conscientiousness = 0.5,
        float $extraversion = 0.5,
        float $agreeableness = 0.5,
        float $neuroticism = 0.3
    ): self {
        $t = array_fill(0, 17, 0.5);
        
        // Mapping heuristic OCEAN -> 17D
        $t[self::CURIOSITY] = $openness;
        $t[self::AMBITION] = $conscientiousness;
        $t[self::DOMINANCE] = $extraversion;
        $t[self::EMPATHY] = $agreeableness;
        $t[self::FEAR] = $neuroticism;
        $t[self::SHAME] = $neuroticism * 0.8;
        $t[self::RISK_TOLERANCE] = $extraversion * 0.7 + $openness * 0.3;
        $t[self::LOYALTY] = $agreeableness * 0.6 + $conscientiousness * 0.4;
        
        return new self($t);
    }

    public function get(int $index): float
    {
        return $this->traits[$index] ?? 0.5;
    }

    public function all(): array
    {
        return $this->traits;
    }

    public static function fromArray(array $data): self
    {
        if (isset($data['traits']) && is_array($data['traits'])) {
            return new self($data['traits']);
        }

        // Fallback to OCEAN if keyed data provided
        return self::fromOcean(
            openness:          (float) ($data['openness']          ?? 0.5),
            conscientiousness: (float) ($data['conscientiousness'] ?? 0.5),
            extraversion:      (float) ($data['extraversion']      ?? 0.5),
            agreeableness:     (float) ($data['agreeableness']     ?? 0.5),
            neuroticism:       (float) ($data['neuroticism']       ?? 0.3),
        );
    }

    public function toArray(): array
    {
        return [
            'traits' => $this->traits,
            // Keep OCEAN for UI backward compatibility
            'openness' => $this->traits[self::CURIOSITY],
            'conscientiousness' => $this->traits[self::AMBITION],
            'extraversion' => $this->traits[self::DOMINANCE],
            'agreeableness' => $this->traits[self::EMPATHY],
            'neuroticism' => $this->traits[self::FEAR],
        ];
    }

    public static function neutral(): self
    {
        return new self(array_fill(0, 17, 0.5));
    }

    /**
     * Lai ghép 2 TraitVector với nhiễu đột biến (Mutation Noise).
     *
     * @param TraitVector $other Đối tác lai ghép.
     * @param float $noise Độ lệch ngẫu nhiên (ví dụ -0.1 đến 0.1).
     * @return self
     */
    public function blend(self $other, float $noise): self
    {
        $newTraits = [];
        for ($i = 0; $i < 17; $i++) {
            // Trung bình cộng + nhiễu
            $val = ($this->get($i) + $other->get($i)) / 2 + $noise;
            $newTraits[$i] = (float) max(0.0, min(1.0, $val));
        }

        return new self($newTraits);
    }

    public function confidence(): float
    {
        // Combined Confidence = 60% Dominance + 40% Ambition
        return (float)($this->get(self::DOMINANCE) * 0.6 + $this->get(self::AMBITION) * 0.4);
    }

    public function neuroticism(): float
    {
        // Neuroticism mapped to FEAR in ocean mapping
        return (float)$this->get(self::FEAR);
    }
}
