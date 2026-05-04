<?php

namespace App\Modules\Psychology\Services;

use App\Modules\Psychology\ValueObjects\PsychologicalState;

/**
 * GoalGenerator – Maslow hierarchy as a dynamic goal priority engine.
 *
 * Goals are NOT preset rules. They EMERGE from the actor's emotional state.
 * Same actor at different moments → different goal priorities.
 *
 * Maslow hierarchy (adapted for simulation):
 *  - Survival  → driven by fear + stress
 *  - Safety    → driven by stress + threat history
 *  - Belong    → driven by low trust + sadness (loneliness)
 *  - Esteem    → driven by sadness + low joy (self-worth)
 *
 * Returns goals sorted by priority (highest first).
 */
final class GoalGenerator
{
    private const ACTIVATION_THRESHOLD = 0.25;

    /**
     * Generate active goals from current psychological state.
     *
     * @return array{type: string, priority: float}[]
     */
    public function generate(PsychologicalState $state): array
    {
        $needs = $this->computeNeeds($state);

        $goals = [];
        foreach ($needs as $type => $priority) {
            if ($priority >= self::ACTIVATION_THRESHOLD) {
                $goals[] = ['type' => $type, 'priority' => round($priority, 3)];
            }
        }

        // Sort: highest priority first
        usort($goals, fn($a, $b) => $b['priority'] <=> $a['priority']);

        return $goals;
    }

    /**
     * Compute Maslow-inspired need intensities from state.
     *
     * @return array<string, float>
     */
    public function computeNeeds(PsychologicalState $state): array
    {
        return [
            'survive' => max(0.0, $state->fear * 0.7 + $state->stress * 0.5),
            'safety'  => max(0.0, $state->stress * 0.8),
            'belong'  => max(0.0, (1.0 - $state->trust) * 0.7 + $state->sadness * 0.3),
            'esteem'  => max(0.0, $state->sadness * 0.6 + (1.0 - $state->joy) * 0.2),
        ];
    }
}
