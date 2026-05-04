<?php

namespace App\Modules\Simulation\Services\Meta;

use App\Modules\World\Models\Universe;
use App\Modules\World\Models\UniverseBridge;

class ConvergenceScoreService
{
    /**
     * Calculate and persist the convergence score between two universes connected by a bridge.
     * Score ∈ [0, 1]. High score means High similarity (convergence).
     */
    public function computeAndSave(UniverseBridge $bridge, int $currentTick): float
    {
        if (!$bridge->sourceUniverse || !$bridge->targetUniverse) {
            return 0.0;
        }

        $source = $bridge->sourceUniverse;
        $target = $bridge->targetUniverse;

        // 1. Entropy Delta (max diff = 1.0)
        $entropyDelta = abs(($source->entropy ?? 0.0) - ($target->entropy ?? 0.0));
        // We want similarity, so invert it
        $entropySimilarity = max(0, 1.0 - $entropyDelta);

        // 2. Stability Delta
        $stabilityDelta = abs(($source->structural_coherence ?? 1.0) - ($target->structural_coherence ?? 1.0));
        $stabilitySimilarity = max(0, 1.0 - $stabilityDelta);

        // 3. State Vector Similarity
        $stateSimilarity = $this->calculateStateSimilarity(
            $source->state_vector ?? [],
            $target->state_vector ?? []
        );

        // Weighted Score (40% entropy, 30% stability, 30% state)
        $score = ($entropySimilarity * 0.4) + ($stabilitySimilarity * 0.3) + ($stateSimilarity * 0.3);
        
        // Ensure within bounds
        $score = max(0.0, min(1.0, $score));

        // Persist
        $bridge->update([
            'convergence_score' => $score,
            'last_synced_tick' => $currentTick,
        ]);

        return $score;
    }

    protected function calculateStateSimilarity(array $vecA, array $vecB): float
    {
        $score = 0.0;
        $comparisons = 0;

        // Compare civilization counts
        $civCountA = $vecA['civilizations_count'] ?? 0;
        $civCountB = $vecB['civilizations_count'] ?? 0;
        if (max($civCountA, $civCountB) > 0) {
            $diff = abs($civCountA - $civCountB);
            $max = max($civCountA, $civCountB);
            $score += max(0, 1 - ($diff / $max));
            $comparisons++;
        }

        // Compare ecosystem type if available
        $envA = $vecA['environment'] ?? [];
        $envB = $vecB['environment'] ?? [];
        if (!empty($envA) && !empty($envB)) {
            // A simple proxy: compare average temperature
            $tempA = $this->calculateAvgTemp($envA);
            $tempB = $this->calculateAvgTemp($envB);
            if ($tempA !== null && $tempB !== null) {
                // assume max temp difference is 50 degrees
                $diff = abs($tempA - $tempB);
                $score += max(0, 1 - ($diff / 50.0));
                $comparisons++;
            }
        }

        if ($comparisons === 0) {
            return 0.5; // fallback neutral similarity
        }

        return $score / $comparisons;
    }

    private function calculateAvgTemp(array $environment): ?float
    {
        if (empty($environment['zones'])) return null;
        $total = 0;
        $count = 0;
        foreach ($environment['zones'] as $zone) {
            if (isset($zone['temperature'])) {
                $total += $zone['temperature'];
                $count++;
            }
        }
        return $count > 0 ? $total / $count : null;
    }
}


