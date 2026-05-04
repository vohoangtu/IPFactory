<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\Intelligence\Domain\Policy\DecisionModel;
use App\Modules\Intelligence\Domain\Policy\UniverseContext;
use App\Modules\Intelligence\Entities\ActorEntity;

/**
 * Pure computation service.
 * Translates (actor, context, decisionModel) into a scored action map.
 * No DB interaction, no side-effects.
 */
class DecisionEngine
{
    /**
     * @return array<string, float>  action => utility score
     */
    public function evaluate(
        ActorEntity    $actor,
        UniverseContext $ctx,
        DecisionModel  $model,
    ): array {
        $features = $this->buildFeatureVector($actor, $ctx);

        $scores = $model->evaluate($features, $ctx);

        // Inject survival pressure: penalise actions that increase personal risk
        $survivalRisk = $ctx->crisisIndex();
        foreach ($scores as $action => &$score) {
            $score += $this->survivalBias($action, $survivalRisk);
        }
        unset($score);

        // Add small stochastic noise for exploration (prevents deterministic lock-in)
        foreach ($scores as &$s) {
            $s += (mt_rand(-8, 8) / 100);
        }
        unset($s);

        return $scores;
    }

    // ── Feature vector ──────────────────────────────────────────────────────

    /**
     * Build a normalised float[] from actor traits + context signals.
     * Index layout: [0..16] = TRAIT_DIMENSIONS, [17] = entropy, [18] = myth, [19] = crisis
     */
    private function buildFeatureVector(ActorEntity $actor, UniverseContext $ctx): array
    {
        $traits = $actor->traits;
        // Normalise traits to [0,1] (they are stored as floats between 0–1 already)
        $features = array_values($traits);

        // Append context signals
        $features[] = $ctx->entropy;
        $features[] = $ctx->mythIntensity;
        $features[] = $ctx->crisisIndex();

        return $features;
    }

    // ── Survival bias ────────────────────────────────────────────────────────

    /**
     * Actors under crisis prefer self-preservation over disruption.
     * Revolt is penalised when survival risk is high; migrate and form_contract are boosted.
     */
    private function survivalBias(string $action, float $survivalRisk): float
    {
        return match ($action) {
            'revolt'          => -$survivalRisk * 0.4,
            'migrate'         => +$survivalRisk * 0.3,
            'form_contract'   => +$survivalRisk * 0.2,
            'suppress_revolt' => +$survivalRisk * 0.1,
            default           => 0.0,
        };
    }
}
