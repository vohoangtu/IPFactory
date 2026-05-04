<?php

namespace App\Modules\Psychology\Services;

use App\Modules\Psychology\ValueObjects\Conflict;
use App\Modules\Psychology\ValueObjects\Impulse;

/**
 * ConflictResolver – applies vector-sum suppression to conflicting impulses.
 *
 * Key principle: suppressed impulses are NOT removed.
 * They can still "leak" into behavior via the 20% chance in DecisionEngine.
 * This creates the "irrationality" that makes actors feel human.
 *
 * Also accumulates stress from unresolved conflict.
 */
final class ConflictResolver
{
    /**
     * Resolve conflicts: suppress weaker impulses, accumulate stress.
     *
     * @param  Impulse[]  $impulses
     * @param  Conflict[] $conflicts
     * @return array{impulses: Impulse[], stress_delta: float}
     */
    public function resolve(array $impulses, array $conflicts): array
    {
        $stressDelta = 0.0;

        foreach ($conflicts as $conflict) {
            $a = $conflict->impulseA;
            $b = $conflict->impulseB;

            // Stress builds from unresolved conflict
            $stressDelta += $conflict->stressContribution();

            // Suppression: weaker is suppressed by factor 0.5 (not eliminated)
            if ($a->intensity > $b->intensity) {
                $b->suppress(0.5);
            } elseif ($b->intensity > $a->intensity) {
                $a->suppress(0.5);
            } else {
                // Equal strength: both partially suppressed (ambivalence)
                $a->suppress(0.7);
                $b->suppress(0.7);
            }
        }

        return [
            'impulses'     => $impulses,
            'stress_delta' => min(0.5, $stressDelta), // cap stress per event
        ];
    }
}
