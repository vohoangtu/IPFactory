<?php

namespace App\Modules\Simulation\Services\Core;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;

/**
 * Adjust module cadence based on current simulation activity signals.
 */
class AdaptiveSchedulerService
{
    public function shouldRun(string $module, Universe $universe, UniverseSnapshot $snapshot): bool
    {
        $tick = (int) $snapshot->tick;
        $state = $universe->state_vector ?? $snapshot->state_vector ?? [];
        $metrics = $snapshot->metrics ?? [];

        $entropy = (float) ($snapshot->entropy ?? 0.5);
        $warActivity = (float) ($state['war_pressure'] ?? $metrics['war_activity'] ?? 0.0);
        $chaosLevel = (float) ($snapshot->instability_gradient ?? $metrics['chaos_level'] ?? 0.0);
        $civKnowledge = (float) ($metrics['civ_fields']['knowledge'] ?? 0.5);

        $baseInterval = $this->getBaseInterval($module);
        if ($baseInterval <= 0) {
            return false;
        }

        if ($tick === 0 || $baseInterval === 1) {
            return true;
        }

        $effectiveInterval = $this->calculateEffectiveInterval(
            $module,
            $baseInterval,
            $entropy,
            $warActivity,
            $chaosLevel,
            $civKnowledge
        );

        return $tick % $effectiveInterval === 0;
    }

    protected function getBaseInterval(string $module): int
    {
        return match ($module) {
            'zone_conflict' => (int) config('worldos.pulse.zone_conflict_interval', 1),
            'idea_diffusion' => (int) config('worldos.idea_diffusion.interval', 5),
            'institution_decay' => (int) config('worldos.institution.decay_interval', 10),
            'actor_decision' => (int) config('worldos.pulse.actor_decision_interval', 1),
            'ideology_evolution' => (int) config('worldos.pulse.ideology_interval', 20),
            'great_person' => (int) config('worldos.pulse.great_person_interval', 50),
            'era_detect' => (int) config('worldos.narrative.era_interval', 200),
            'mythology' => (int) config('worldos.narrative.mythology_interval', 50),
            'religion_spread' => (int) config('worldos.narrative.religion_interval', 200),
            'causal_trajectory' => (int) config('worldos.narrative.causal_trajectory_interval', 500),
            'legend' => (int) config('worldos.narrative.legend_interval', 100),
            default => 10,
        };
    }

    protected function calculateEffectiveInterval(
        string $module,
        int $base,
        float $entropy,
        float $warActivity,
        float $chaosLevel,
        float $civKnowledge
    ): int {
        $modifier = 1.0;

        switch ($module) {
            case 'zone_conflict':
                if ($warActivity > 0.7 || $chaosLevel > 0.8) {
                    $modifier = 0.2;
                } elseif ($warActivity > 0.4 || $chaosLevel > 0.5) {
                    $modifier = 0.5;
                }
                break;

            case 'idea_diffusion':
            case 'actor_decision':
                if ($civKnowledge > 0.8 || $chaosLevel > 0.8) {
                    $modifier = 0.5;
                }
                break;

            case 'institution_decay':
            case 'ideology_evolution':
                if ($entropy > 0.8 || $chaosLevel > 0.7) {
                    $modifier = 0.25;
                }
                break;

            case 'mythology':
                if ($entropy > 0.7 || $chaosLevel > 0.6 || $warActivity > 0.55) {
                    $modifier = 0.5;
                }
                break;

            case 'great_person':
                if ($chaosLevel > 0.8 || $warActivity > 0.8) {
                    $modifier = 0.5;
                }
                break;
        }

        return (int) max(1, round($base * $modifier));
    }
}
