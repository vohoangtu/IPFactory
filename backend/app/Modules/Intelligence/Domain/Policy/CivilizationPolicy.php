<?php

namespace App\Modules\Intelligence\Domain\Policy;

/**
 * Domain Entity: represents one generation of civilization policy.
 * Owns the priority weights for the FitnessEvaluator.
 * Pure PHP — no Eloquent.
 */
class CivilizationPolicy
{
    public function __construct(
        public readonly string  $id,
        public readonly int     $generation,
        public readonly ?string $parentPolicyId,
        public readonly ?string $arenaBatchId,
        public float            $survivalPriority,
        public float            $stabilityPriority,
        public float            $diversityPriority,
        public ?float           $fitnessScore = null,
    ) {}

    /**
     * True if this policy beat the given other — used for selection.
     * Pareto-simplified: we compare total fitness only.
     */
    public function dominates(self $other): bool
    {
        if ($this->fitnessScore === null || $other->fitnessScore === null) {
            return false;
        }

        return $this->fitnessScore > $other->fitnessScore;
    }

    public function isElite(float $threshold): bool
    {
        return ($this->fitnessScore ?? 0.0) >= $threshold;
    }

    public static function seed(?string $batchId = null): self
    {
        return new self(
            id:               (string) \Illuminate\Support\Str::uuid(),
            generation:       1,
            parentPolicyId:   null,
            arenaBatchId:     $batchId,
            survivalPriority:  1.0,
            stabilityPriority: 0.6,
            diversityPriority: 0.4,
        );
    }
}
