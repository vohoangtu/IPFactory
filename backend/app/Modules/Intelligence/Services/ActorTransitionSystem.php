<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\Intelligence\Entities\ActorState;
use App\Modules\Intelligence\Domain\Rng\SimulationRng;
use App\Modules\Intelligence\Domain\Entropy\EntropyBudget;

/**
 * Pure functions to transition ActorState based on Simulation Engine inputs.
 * Strictly adheres to Determinism by exclusively using SimulationRng and EntropyBudget.
 */
class ActorTransitionSystem
{
    /**
     * Drift traits smoothly logic using deterministic RNG.
     */
    public function driftTraits(
        ActorState $state,
        SimulationRng $rng,
        EntropyBudget $budget,
        array $socialField = [],
        float $variance = 0.02
    ): ActorState {
        if (!$state->isAlive) {
            return $state;
        }

        $newTraits = $state->traits;
        foreach ($newTraits as $key => $val) {
            // Replaces rand(-100, 100) / 100.0 with deterministic RNG
            // e.g nextFloat returns [0, 1), we map it to [-1, 1]
            $drift = ($rng->nextFloat() * 2 - 1) * $variance;
            $newTraits[$key] = max(0, min(1, $val + $drift));
        }

        return $state->with(['traits' => $newTraits]);
    }

    /**
     * Survival check using logistic probability against state and entropy.
     * Uses mortality curve by age_ratio (age / life_expectancy): young = low death prob, old = high.
     */
    public function processSurvival(
        ActorState $state,
        float $entropy,
        SimulationRng $rng,
        float $ageRatio = 0.0,
        float $fitness = 1.0, // This will map to survival_modifier from MetabolicEngine
        float $collapseDeathProbAdd = 0.0
    ): ActorState {
        if (!$state->isAlive) {
            return $state;
        }

        // Resilience (trait 10), Longevity (17). Thể chất: metrics['physic'] (aggregate).
        $resilience = max(0, min(1, (float) ($state->traits['resilience'] ?? $state->traits[10] ?? 0.5)));
        $longevity = max(0, min(1, (float) ($state->traits['longevity'] ?? $state->traits['Longevity'] ?? $state->traits[17] ?? 0.5)));
        $physicAggregate = $this->aggregatePhysic(
            $state->metrics['physic'] ?? null,
            $state->metrics['injury'] ?? null
        );
        $logit = $resilience * 0.35 + $longevity * 0.15 + $physicAggregate * 0.15 + (1 - $entropy) * 0.35;
        $prob = 1 / (1 + exp(-$logit));

        // Starvation (energy economy): reduce survival when starving
        $starving = !empty($state->metrics['starving']) || $fitness < 1.0;
        if ($starving) {
            $prob *= max(0.5, $fitness); // Apply metabolic pressure
        }

        $baselineDeathChance = 0.015;
        $prob = $prob * (1 - $baselineDeathChance);

        // Mortality curve: age_ratio < 0.6 → low, < 1.0 → mid, >= 1.0 → high death prob
        $deathProbFromAge = $this->mortalityCurveDeathProb($ageRatio);
        $prob = $prob * (1.0 - $deathProbFromAge);

        // Evolution pressure: fitness from environment (0.2–1.0)
        $prob = $prob * max(0.2, min(1.0, $fitness));

        // Ecological collapse: extra death probability when collapse is active
        if ($collapseDeathProbAdd > 0) {
            $prob = $prob * (1.0 - $collapseDeathProbAdd);
        }

        if ($rng->nextFloat() > $prob) {
            return $state->with(['isAlive' => false]);
        }

        return $state;
    }

    /**
     * Return death probability per tick from mortality curve config (age_ratio = age / life_expectancy).
     */
    private function mortalityCurveDeathProb(float $ageRatio): float
    {
        $curve = config('worldos.intelligence.mortality_curve', [
            '0.6' => 0.001,
            '1.0' => 0.01,
            'old' => 0.2,
        ]);
        if ($ageRatio >= 1.0) {
            return (float) ($curve['old'] ?? 0.2);
        }
        if ($ageRatio >= 0.6) {
            return (float) ($curve['1.0'] ?? 0.01);
        }
        return (float) ($curve['0.6'] ?? 0.001);
    }

    /**
     * Aggregate physic vector (huyết mạch) to a single scalar for survival.
     * If metrics['injury'] is set (per-dimension 0–1), effective physic is reduced.
     */
    private function aggregatePhysic(?array $physic, ?array $injury = null): float
    {
        if ($physic === null || $physic === []) {
            return 0.5;
        }
        $vals = array_values($physic);
        $out = [];
        foreach ($vals as $i => $v) {
            $x = is_numeric($v) ? max(0, min(1, (float) $v)) : 0.5;
            if ($injury !== null && isset($injury[$i]) && is_numeric($injury[$i])) {
                $x *= 1.0 - max(0, min(1, (float) $injury[$i]));
            }
            $out[] = $x;
        }
        $n = count($out);
        return $n > 0 ? array_sum($out) / $n : 0.5;
    }

    /**
     * Updates traits directly from successful actions.
     */
    public function evolveTraits(ActorState $state, array $successfulActions): ActorState
    {
        if (!$state->isAlive) {
            return $state;
        }

        $newTraits = $state->traits;
        foreach ($successfulActions as $action) {
            $trait = $this->mapActionToTrait($action);
            if ($trait) {
                $newTraits[$trait] = min(1.0, ($newTraits[$trait] ?? 0.1) + 0.05);
            }
        }

        return $state->with(['traits' => $newTraits]);
    }

    private function mapActionToTrait(string $action): ?string
    {
        return match($action) {
            'combat' => 'strength',
            'research' => 'intelligence',
            'trade' => 'charisma',
            default => null
        };
    }
}
