<?php

namespace App\Modules\Intelligence\Domain\Policy;

/**
 * Immutable result of the fitness function for one universe run.
 * Three-tier priority: Survival > Stability > Diversity.
 */
final class FitnessScore
{
    public function __construct(
        public readonly float $survivalScore,
        public readonly float $stabilityScore,
        public readonly float $diversityScore,
        public readonly float $complexityPenalty = 0.0,
    ) {}

    /**
     * Survival (1.0) + Stability (0.6) + Diversity (survival²) − complexity
     * If extinction detected upstream, survival = 0 → diversity collapses naturally.
     */
    public function total(): float
    {
        $diversityWeight = $this->survivalScore ** 2;

        return round(
            $this->survivalScore * 1.0
            + $this->stabilityScore * 0.6
            + $this->diversityScore * $diversityWeight
            - $this->complexityPenalty,
            6
        );
    }

    public function isExtinct(): bool
    {
        return $this->survivalScore <= 0.0;
    }

    public static function extinction(): self
    {
        return new self(0.0, 0.0, 0.0, 0.0);
    }
}
