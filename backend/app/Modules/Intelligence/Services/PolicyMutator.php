<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\Intelligence\Domain\Policy\DecisionModel;
use App\Modules\Intelligence\Domain\Policy\ModelType;
use Illuminate\Support\Str;

/**
 * Evolves DecisionModel instances across three mutation levels:
 *   1. Parameter mutation (weight ± delta)
 *   2. Structural mutation (upgrade/downgrade model type)
 *   3. Context mutation (add/adjust entropy/myth adaptive weights)
 *
 * Adaptive rate: collapses trigger burst or conservative mutation.
 */
class PolicyMutator
{
    /** Minimum stagnation penalty for the fitness function (parameters × this). */
    private const COMPLEXITY_COST = 0.01;

    // ── Public API ──────────────────────────────────────────────────────────

    /**
     * Evolve a set of elite models into next-generation candidates.
     *
     * @param  DecisionModel[] $elites
     * @param  int             $nextGeneration
     * @param  float           $survivalScore   [0,1] — drives adaptive rate
     * @param  float           $diversityScore  [0,1] — low diversity → chaos burst
     * @return DecisionModel[]
     */
    public function evolve(array $elites, int $nextGeneration, float $survivalScore, float $diversityScore): array
    {
        $mutationRate = $this->adaptiveRate($survivalScore, $diversityScore);
        $offspring    = [];

        foreach ($elites as $parent) {
            $child = $this->mutateParameters($parent, $mutationRate);
            $child = $this->mutateStructure($child, $diversityScore);
            $child = $this->mutateContextWeights($child, $diversityScore);

            $offspring[] = $this->rekey($child, $parent->policyId, $nextGeneration);
        }

        return $offspring;
    }

    // ── Level 1: Parameter mutation ─────────────────────────────────────────

    public function mutateParameters(DecisionModel $model, float $rate): DecisionModel
    {
        $weights = [];
        foreach ($model->weightVector as $action => $vec) {
            $weights[$action] = array_map(
                fn($w) => round(max(-2.0, min(2.0, $w + $this->noise($rate))), 5),
                $vec
            );
        }

        $thresholds = [];
        foreach ($model->thresholdVector as $action => $t) {
            $thresholds[$action] = round(max(0.5, min(3.0, $t + $this->noise($rate * 0.5))), 4);
        }

        return $this->clone($model, weightVector: $weights, thresholdVector: $thresholds);
    }

    // ── Level 2: Structural mutation (model type evolution) ─────────────────

    public function mutateStructure(DecisionModel $model, float $diversityScore): DecisionModel
    {
        if (mt_rand(0, 99) >= 10) {
            return $model; // 10% chance to mutate structure
        }

        $complexity = $model->parameterCount() * self::COMPLEXITY_COST;

        // Low diversity → exploration burst: upgrade model type
        if ($diversityScore < 0.3) {
            $newType = $this->upgradeType($model->modelType);
            $model   = $this->clone($model, modelType: $newType);

            if ($newType === ModelType::POLYNOMIAL && empty($model->interactionMatrix)) {
                $model = $this->seedInteractionMatrix($model);
            }
        }

        // High complexity → simplify to keep fitness
        if ($complexity > 0.5) {
            $newType = $this->downgradeType($model->modelType);
            $model   = $this->clone($model, modelType: $newType);
        }

        return $model;
    }

    // ── Level 3: Context weight mutation ────────────────────────────────────

    public function mutateContextWeights(DecisionModel $model, float $diversityScore): DecisionModel
    {
        if (mt_rand(0, 99) >= 15) {
            return $model; // 15% chance
        }

        $ctx = $model->contextWeights;
        $ctx['entropy_scale'] = round(max(-0.5, min(0.5, ($ctx['entropy_scale'] ?? 0.0) + $this->noise(0.03))), 4);
        $ctx['myth_scale']    = round(max(-0.5, min(0.5, ($ctx['myth_scale'] ?? 0.0) + $this->noise(0.03))), 4);

        // If diversity collapsed, force context_aware mode
        if ($diversityScore < 0.2 && $model->modelType !== ModelType::CONTEXT_AWARE) {
            return $this->clone($model, modelType: ModelType::CONTEXT_AWARE, contextWeights: $ctx);
        }

        return $this->clone($model, contextWeights: $ctx);
    }

    // ── Complexity penalty (used externally by FitnessEvaluator) ────────────

    public function complexityPenalty(DecisionModel $model): float
    {
        return round($model->parameterCount() * self::COMPLEXITY_COST, 6);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function adaptiveRate(float $survival, float $diversity): float
    {
        if ($survival < 0.2) return 0.03;   // survival collapse → conservative
        if ($diversity < 0.25) return 0.12; // diversity collapse → chaos burst
        return 0.05;                          // normal
    }

    private function noise(float $rate): float
    {
        return (mt_rand(-1000, 1000) / 1000) * $rate;
    }

    private function upgradeType(ModelType $current): ModelType
    {
        return match ($current) {
            ModelType::LINEAR        => ModelType::SIGMOID,
            ModelType::SIGMOID       => ModelType::POLYNOMIAL,
            ModelType::POLYNOMIAL    => ModelType::CONTEXT_AWARE,
            ModelType::CONTEXT_AWARE => ModelType::CONTEXT_AWARE,
        };
    }

    private function downgradeType(ModelType $current): ModelType
    {
        return match ($current) {
            ModelType::CONTEXT_AWARE => ModelType::POLYNOMIAL,
            ModelType::POLYNOMIAL    => ModelType::SIGMOID,
            ModelType::SIGMOID       => ModelType::LINEAR,
            ModelType::LINEAR        => ModelType::LINEAR,
        };
    }

    private function seedInteractionMatrix(DecisionModel $model): DecisionModel
    {
        $dim = count(array_values($model->weightVector)[0] ?? []);
        $matrix = [];

        foreach ($model->weightVector as $action => $_) {
            // Sparse: seed only first 3×3 interactions with small noise
            for ($i = 0; $i < min(3, $dim); $i++) {
                for ($j = $i + 1; $j < min(3, $dim); $j++) {
                    $matrix[$action][$i][$j] = round($this->noise(0.05), 4);
                }
            }
        }

        return $this->clone($model, interactionMatrix: $matrix);
    }

    private function rekey(DecisionModel $model, string $policyId, int $generation): DecisionModel
    {
        return new DecisionModel(
            id:                (string) Str::uuid(),
            policyId:          $policyId,
            universeId:        $model->universeId,
            modelType:         $model->modelType,
            weightVector:      $model->weightVector,
            interactionMatrix: $model->interactionMatrix,
            thresholdVector:   $model->thresholdVector,
            contextWeights:    $model->contextWeights,
            generation:        $generation,
        );
    }

    private function clone(
        DecisionModel $model,
        ?ModelType    $modelType         = null,
        ?array        $weightVector      = null,
        ?array        $interactionMatrix = null,
        ?array        $thresholdVector   = null,
        ?array        $contextWeights    = null,
    ): DecisionModel {
        return new DecisionModel(
            id:                $model->id,
            policyId:          $model->policyId,
            universeId:        $model->universeId,
            modelType:         $modelType         ?? $model->modelType,
            weightVector:      $weightVector      ?? $model->weightVector,
            interactionMatrix: $interactionMatrix ?? $model->interactionMatrix,
            thresholdVector:   $thresholdVector   ?? $model->thresholdVector,
            contextWeights:    $contextWeights    ?? $model->contextWeights,
            generation:        $model->generation,
        );
    }
}


