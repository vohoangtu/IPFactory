<?php

namespace App\Modules\Simulation\Services\Cosmology;

use App\Modules\Simulation\Models\UniverseSnapshot;

/**
 * Cosmic Phase = dominant axis (faith / chaos / order / tech) with hysteresis.
 * Only change phase when new_dominant_score > old_dominant_score + hysteresis.
 */
class CosmicPhaseDetector
{
    private const AXES = ['faith', 'chaos', 'order', 'tech'];

    public function detect(UniverseSnapshot $snapshot, array $metrics): array
    {
        $ethos = $metrics['ethos'] ?? [];
        $entropy = max(0.0, min(1.0, (float) ($snapshot->entropy ?? 0.5)));

        $faithScore = max(0.0, min(1.0, (float) ($ethos['spirituality'] ?? 0.5)));
        $chaosScore = $entropy;
        $orderScore = 1.0 - $entropy;
        $techScore = max(0.0, min(1.0, (float) ($ethos['openness'] ?? $ethos['rationality'] ?? 0.5)));

        $scores = [
            'faith' => $faithScore,
            'chaos' => $chaosScore,
            'order' => $orderScore,
            'tech' => $techScore,
        ];

        $dominant = $this->argmax($scores);
        $dominantScore = $scores[$dominant];

        $previous = $metrics['cosmic_phase'] ?? [];
        $previousPhase = $previous['current_phase'] ?? null;
        $previousScore = (float) ($previous['previous_dominant_score'] ?? 0.0);
        $hysteresis = (float) config('worldos.cosmic_phase.hysteresis', 0.15);

        $newPhase = $dominant;
        $newStrength = $dominantScore;
        if ($previousPhase !== null && isset($scores[$previousPhase])) {
            $currentPreviousScore = $scores[$previousPhase];
            if ($dominantScore <= $currentPreviousScore + $hysteresis) {
                $newPhase = $previousPhase;
                $newStrength = $scores[$previousPhase];
            }
        }

        return [
            'current_phase' => $newPhase,
            'phase_strength' => max(0.0, min(1.0, $newStrength)),
            'previous_dominant_score' => $newStrength,
            'scores' => $scores,
        ];
    }

    private function argmax(array $scores): string
    {
        $max = -1.0;
        $key = 'faith';
        foreach ($scores as $k => $v) {
            if ($v > $max) {
                $max = $v;
                $key = $k;
            }
        }
        return $key;
    }
}

