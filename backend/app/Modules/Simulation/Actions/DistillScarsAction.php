<?php

namespace App\Modules\Simulation\Actions;

use App\Modules\Narrative\Models\Chronicle;
use Illuminate\Support\Facades\Log;

/**
 * DistillScarsAction - V10: Narrative Layer
 * 
 * Filters raw gRPC event scars into significant historical imprints.
 * Prevents log spam and ensures collective memory is impactful.
 */
class DistillScarsAction
implements \App\Contracts\ActionInterface {
    private const HIGH_IMPACT_CATEGORIES = [
        'WAR', 'REVOLT', 'FAMINE', 'PESTILENCE', 'EXTINCTION',
        'CALAMITY', 'COLLAPSE', 'CIVILIZATION_FALL', 'LEADERSHIP_CHANGE',
        'DISASTER', 'DEATH'
    ];

    private const MAGNITUDE_THRESHOLD = 7.0;

    /**
     * Distill and record significant scars into Chronicles.
     */
    public function handle(int $universeId, int $tick, array $rawScars): void
    {
        if (empty($rawScars)) return;

        $significantScars = $this->distill($rawScars);

        foreach ($significantScars as $scar) {
            $category = strtoupper($scar['category'] ?? 'EMERGENT_SCAR');
            $magnitude = (float) ($scar['raw_payload']['magnitude'] ?? $scar['magnitude'] ?? 8.0);
            
            Chronicle::create([
                'universe_id' => $universeId,
                'actor_id' => ($scar['actor_id'] && $scar['actor_id'] > 0) ? $scar['actor_id'] : null,
                'from_tick' => (int)($scar['tick'] ?? $tick),
                'to_tick' => (int)($scar['tick'] ?? $tick),
                'type' => $category,
                'content' => $scar['description'] ?? 'Unnamed emergent event',
                'importance' => max(0.2, min(1.0, $magnitude / 10.0)),
                'raw_payload' => $scar
            ]);

            Log::debug("DistillScarsAction: Significant scar recorded for Universe {$universeId}: {$category}");
        }
    }

    /**
     * Filter and merge scars based on importance and redundancy.
     */
    private function distill(array $scars): array
    {
        $filtered = array_filter($scars, function($scar) {
            $magnitude = (float) ($scar['raw_payload']['magnitude'] ?? $scar['magnitude'] ?? 0.0);
            $category = strtoupper($scar['category'] ?? '');

            // Rule 1: High Magnitude events (Always significant)
            if ($magnitude >= self::MAGNITUDE_THRESHOLD) return true;

            // Rule 2: Irreversible/Critical Categories
            foreach (self::HIGH_IMPACT_CATEGORIES as $cat) {
                if (str_contains($category, $cat)) return true;
            }

            // Rule 3: Random sample for variety (Very low chance)
            if (mt_rand(1, 1000) === 1) return true;

            return false;
        });

        // Rule 4: Merge similar events in the same batch to avoid redundancy
        return $this->mergeByType($filtered);
    }

    /**
     * If multiple events of the same type occur in one batch (same tick), 
     * only keep the one with the highest magnitude.
     */
    private function mergeByType(array $scars): array
    {
        $merged = [];
        $seen = [];

        foreach ($scars as $scar) {
            $type = strtoupper($scar['category'] ?? 'UNKNOWN');
            $magnitude = (float) ($scar['raw_payload']['magnitude'] ?? $scar['magnitude'] ?? 0.0);

            if (isset($seen[$type])) {
                $existingIdx = $seen[$type];
                $existingMag = (float) ($merged[$existingIdx]['raw_payload']['magnitude'] ?? $merged[$existingIdx]['magnitude'] ?? 0.0);
                
                if ($magnitude > $existingMag) {
                    $merged[$existingIdx] = $scar;
                }
                continue;
            }

            $merged[] = $scar;
            $seen[$type] = count($merged) - 1;
        }

        return $merged;
    }
}
