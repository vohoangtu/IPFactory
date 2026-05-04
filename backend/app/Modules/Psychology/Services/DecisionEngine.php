<?php

namespace App\Modules\Psychology\Services;

use App\Modules\Psychology\Dsl\BehaviorDslLoader;
use App\Modules\Psychology\Dsl\ExpressionEngine;
use App\Modules\Psychology\ValueObjects\Impulse;
use App\Modules\Psychology\ValueObjects\PsychologicalState;

/**
 * DecisionEngine – probabilistic behavior selection.
 *
 * NOT deterministic. Uses softmax + noise so the same state
 * doesn't always produce the same behavior.
 *
 * Pipeline:
 * 1. Score each DSL behavior via ExpressionEngine
 * 2. Add goal influences (vector-sum from Maslow goals)
 * 3. Add impulse overrides (dominant impulse boosts its mapped behavior)
 * 4. Add noise (micro-randomness)
 * 5. Softmax → probabilistic sample
 *
 * This creates "human-like inconsistency" even under the same state.
 */
final class DecisionEngine
{
    private const NOISE_LEVEL     = 0.15; // ±noise magnitude
    private const LEAKAGE_CHANCE  = 20;   // % chance suppressed impulse leaks

    public function __construct(
        private readonly BehaviorDslLoader $dslLoader,
        private readonly ExpressionEngine  $expressionEngine,
    ) {}

    /**
     * Decide the next behavior given the psychological context.
     *
     * @param  PsychologicalState         $state    Current emotional state
     * @param  array{type,priority}[]     $goals    From GoalGenerator
     * @param  Impulse[]                  $impulses Resolved (post-conflict) impulses
     * @param  array<string,float>        $extraContext Additional DSL vars (trauma, entropy…)
     * @return string  Behavior name (e.g. 'withdraw', 'cooperate')
     */
    public function decide(
        PsychologicalState $state,
        array              $goals    = [],
        array              $impulses = [],
        array              $extraContext = [],
    ): string {
        $behaviors = $this->dslLoader->behaviors();
        $goalDefs  = $this->dslLoader->goals();

        if (empty($behaviors)) {
            return 'passive';
        }

        $ctx = array_merge($state->toArray(), $extraContext);

        // Step 1: Base scores from DSL expressions
        $scores = [];
        foreach ($behaviors as $behavior) {
            $name  = $behavior['name'] ?? 'unknown';
            $expr  = $behavior['base_score'] ?? '0';
            $scores[$name] = $this->expressionEngine->evaluate($expr, $ctx);
        }

        // Step 2: Goal influences (vector-sum)
        foreach ($goalDefs as $goalDef) {
            // Find if this goal type is active
            $activeGoal = null;
            foreach ($goals as $g) {
                if ($g['type'] === $goalDef['type']) {
                    $activeGoal = $g;
                    break;
                }
            }
            if ($activeGoal === null) {
                continue;
            }

            $goalPriority = (float) $activeGoal['priority'];
            foreach ($goalDef['influences'] ?? [] as $influence) {
                $behaviorName = $influence['behavior'] ?? '';
                if (isset($scores[$behaviorName])) {
                    $influenceExpr  = $influence['score'] ?? '0';
                    $influenceValue = $this->expressionEngine->evaluate($influenceExpr, $ctx);
                    $scores[$behaviorName] += $influenceValue * $goalPriority;
                }
            }
        }

        // Step 3: Impulse override – dominant impulse maps to behavior
        if (!empty($impulses)) {
            $dominant = $this->dominantImpulse($impulses);
            $mapped   = $this->mapActionToBehavior($dominant->action);
            if (isset($scores[$mapped])) {
                $scores[$mapped] += $dominant->intensity * 0.5;
            }

            // Impulse leakage: suppressed impulse has chance to surface
            if (mt_rand(0, 99) < self::LEAKAGE_CHANCE && count($impulses) > 1) {
                $suppressed = $this->secondImpulse($impulses);
                $leakBehavior = $this->mapActionToBehavior($suppressed->action);
                if (isset($scores[$leakBehavior])) {
                    $scores[$leakBehavior] += $suppressed->intensity * 0.3;
                }
            }
        }

        // Step 4: Add noise
        foreach ($scores as $name => $score) {
            $noise = (mt_rand(-1000, 1000) / 1000) * self::NOISE_LEVEL;
            $scores[$name] = $score + $noise;
        }

        // Step 5: Softmax sampling
        return $this->softmaxSample($scores);
    }

    // ─────────────────── Private ───────────────────

    private function dominantImpulse(array $impulses): Impulse
    {
        usort($impulses, fn(Impulse $a, Impulse $b) => $b->intensity <=> $a->intensity);
        return $impulses[0];
    }

    private function secondImpulse(array $impulses): Impulse
    {
        usort($impulses, fn(Impulse $a, Impulse $b) => $b->intensity <=> $a->intensity);
        return $impulses[1] ?? $impulses[0];
    }

    private function mapActionToBehavior(string $action): string
    {
        return match ($action) {
            Impulse::ACTION_AVOID, Impulse::ACTION_WITHDRAW => 'withdraw',
            Impulse::ACTION_ATTACK                         => 'resist',
            Impulse::ACTION_COOPERATE                      => 'cooperate',
            Impulse::ACTION_DEFEND                         => 'resist',
            Impulse::ACTION_APPROACH                       => 'cooperate',
            default                                        => 'passive',
        };
    }

    /**
     * Sample behavior using softmax distribution.
     * Higher scores are exponentially more likely, but not guaranteed.
     *
     * @param  array<string, float> $scores
     */
    private function softmaxSample(array $scores): string
    {
        if (empty($scores)) {
            return 'passive';
        }

        // Shift to avoid extreme exp overflow
        $max = max($scores);
        $exps = [];
        $sum  = 0.0;
        foreach ($scores as $name => $score) {
            $exp = exp($score - $max);
            $exps[$name] = $exp;
            $sum += $exp;
        }

        if ($sum <= 0) {
            return array_key_first($scores);
        }

        // Sample
        $rand = (mt_rand() / mt_getrandmax());
        $cumulative = 0.0;
        foreach ($exps as $name => $exp) {
            $cumulative += $exp / $sum;
            if ($rand <= $cumulative) {
                return $name;
            }
        }

        return array_key_last($scores);
    }
}
