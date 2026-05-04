<?php

namespace App\Modules\Intelligence\Domain\Policy;

use App\Modules\Intelligence\Entities\ActorEntity;

/**
 * Domain entity representing a generational decision model.
 * Pure PHP — no Eloquent. Encapsulates the pluggable activation function logic.
 */
class DecisionModel
{
    public function __construct(
        public readonly string    $id,
        public readonly string    $policyId,
        public readonly int       $universeId,
        public ModelType          $modelType,
        /** @var array<string, float[]> action => weight_per_trait */
        public array              $weightVector,
        /** @var array<string, float[][]> action => matrix for polynomial cross-terms */
        public array              $interactionMatrix,
        /** @var array<string, float> action => threshold */
        public array              $thresholdVector,
        /** @var array<string, float> key => adaptive scale for entropy/myth context */
        public array              $contextWeights,
        public readonly int       $generation,
    ) {}

    /**
     * Evaluate raw utility scores for all actions.
     * Returns [action => score] using the model's activation strategy.
     *
     * @param  float[]         $features  Normalized trait vector (size = TRAIT_DIMENSIONS)
     * @param  UniverseContext $ctx
     * @return array<string, float>
     */
    public function evaluate(array $features, UniverseContext $ctx): array
    {
        $scores = [];

        foreach ($this->weightVector as $action => $weights) {
            $scores[$action] = match ($this->modelType) {
                ModelType::LINEAR        => $this->linear($features, $weights),
                ModelType::SIGMOID       => $this->sigmoid($this->linear($features, $weights)),
                ModelType::POLYNOMIAL    => $this->polynomial($features, $weights, $this->interactionMatrix[$action] ?? []),
                ModelType::CONTEXT_AWARE => $this->contextAware($features, $weights, $ctx),
            };
        }

        return $scores;
    }

    // ── Activation functions ────────────────────────────────────────────────

    private function linear(array $features, array $weights): float
    {
        $score = 0.0;
        foreach ($weights as $i => $w) {
            $score += ($features[$i] ?? 0.0) * $w;
        }
        return $score;
    }

    private function sigmoid(float $x): float
    {
        return 1.0 / (1.0 + exp(-$x));
    }

    private function polynomial(array $features, array $weights, array $matrix): float
    {
        $score = $this->linear($features, $weights);

        // Add cross-term interactions: Σ(W₂_ij * X_i * X_j)
        foreach ($matrix as $i => $row) {
            foreach ($row as $j => $w) {
                $score += $w * ($features[$i] ?? 0.0) * ($features[$j] ?? 0.0);
            }
        }

        return $score;
    }

    private function contextAware(array $features, array $weights, UniverseContext $ctx): float
    {
        // Effective weight = base_weight + entropy_shift * context_scale
        $entropyShift = $ctx->entropy * ($this->contextWeights['entropy_scale'] ?? 0.0);
        $mythShift    = $ctx->mythIntensity * ($this->contextWeights['myth_scale'] ?? 0.0);

        $modifiedWeights = array_map(
            fn($w) => $w + $entropyShift + $mythShift,
            $weights
        );

        return $this->linear($features, $modifiedWeights);
    }

    /** Complexity measure for fitness penalty (number of non-zero parameters). */
    public function parameterCount(): int
    {
        $count = count(array_merge(...array_values($this->weightVector)));
        foreach ($this->interactionMatrix as $matrix) {
            $count += count(array_merge(...array_values($matrix ?: [[]])));
        }
        return $count;
    }

    /** Build the default linear model for a fresh universe. */
    public static function defaultLinear(string $id, string $policyId, int $universeId, int $generation): self
    {
        // 17-dimension weight vector per action, all zeroed — PolicyMutator will seed
        $dimensions = count(\App\Modules\Intelligence\Entities\ActorEntity::TRAIT_DIMENSIONS);
        $zeroWeights = array_fill(0, $dimensions, 0.0);

        return new self(
            id:                $id,
            policyId:          $policyId,
            universeId:        $universeId,
            modelType:         ModelType::LINEAR,
            weightVector:      [
                'revolt'          => $zeroWeights,
                'form_contract'   => $zeroWeights,
                'migrate'         => $zeroWeights,
                'trade'           => $zeroWeights,
                'suppress_revolt' => $zeroWeights,
                'propagate_myth'  => $zeroWeights,
            ],
            interactionMatrix: [],
            thresholdVector:   [
                'revolt'          => 1.2,
                'form_contract'   => 1.0,
                'migrate'         => 1.1,
                'trade'           => 1.0,
                'suppress_revolt' => 1.1,
                'propagate_myth'  => 1.0,
            ],
            contextWeights:    ['entropy_scale' => 0.0, 'myth_scale' => 0.0],
            generation:        $generation,
        );
    }
}
