<?php

namespace App\Modules\Intelligence\Services\Lab;

use App\Contracts\SimulationEngineClientInterface;

/**
 * Layer 10: Control Engine.
 * Automatically searches for optimal governance sequences (attractor activations) 
 * to rescue a failing civilization.
 */
class ControlEngine
{
    public function __construct(
        private SimulationEngineClientInterface $engine
    ) {}

    /**
     * Search for a sequence of attractors that prevents collapse.
     * 
     * @param array $failingState The state of the civilization immediately preceding collapse.
     * @return array Optimal sequence of attractor IDs to activate over the next 10 ticks.
     */
    public function searchOptimalGovernance(array $failingState): array
    {
        // Simple search heuristic using parallel batches
        // Try injecting different "force_map" vectors representing attractors.
        $strategies = [
            ['order' => 1.5],          // Strategy A: Enforce strict order
            ['knowledge' => 1.5],      // Strategy B: Push for technological breakthrough
            ['coercion' => -1.0],      // Strategy C: Reduce oppression
            ['entropy' => -0.5],       // Strategy D: Active stabilization
        ];

        $requests = [];
        foreach ($strategies as $idx => $forces) {
            $requests[] = [
                'universe_id' => 100 + $idx,
                'ticks' => 20, // Simulate 20 ticks ahead
                'state_input' => $failingState,
                // Simulate the attractor's effect by augmenting the config or via a metadata hack 
                // for the engine to pick up (assuming engine supports this).
                'metadata' => [
                    'active_attractor_forces' => $forces
                ]
            ];
        }

        $batchResult = $this->engine->batchAdvance($requests);
        $responses = $batchResult['responses'] ?? [];

        $bestStrategy = null;
        $highestStability = -1.0;

        foreach ($responses as $idx => $res) {
            if (!($res['ok'] ?? false)) continue;

            $stability = $res['snapshot']['stability_index'] ?? 0.0;
            if ($stability > $highestStability) {
                $highestStability = $stability;
                $bestStrategy = $strategies[$idx];
            }
        }

        if ($highestStability > 0.3) {
            return [
                'success' => true,
                'strategy' => $bestStrategy,
                'projected_stability' => $highestStability
            ];
        }

        return [
            'success' => false,
            'message' => 'No viable strategy found. Collapse is deterministic.',
        ];
    }
}
