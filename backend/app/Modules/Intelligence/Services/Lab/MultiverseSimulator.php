<?php

namespace App\Modules\Intelligence\Services\Lab;

use App\Contracts\SimulationEngineClientInterface;

/**
 * Layer 10: Multiverse Simulator.
 * Orchestrates massive parallel simulations to map the civilization phase space.
 */
class MultiverseSimulator
{
    public function __construct(
        private SimulationEngineClientInterface $engine
    ) {}

    /**
     * Run a grid search over the phase space to find stable regions.
     * 
     * @param array $baseState Starting canonical state.
     * @param array $configVariations Array of world_config variations to test.
     * @param int $horizon Number of ticks to simulate.
     * @return array Analytical result of all variations.
     */
    public function runGridSearch(array $baseState, array $configVariations, int $horizon = 100): array
    {
        $requests = [];
        foreach ($configVariations as $idx => $config) {
            $requests[] = [
                'universe_id' => $idx + 1000, // Ephemeral universes
                'ticks' => $horizon,
                'state_input' => $baseState,
                'world_config' => $config,
            ];
        }

        // Run all parallel in Rust
        $batchResult = $this->engine->batchAdvance($requests);
        $responses = $batchResult['responses'] ?? [];

        return $this->analyzeGridResults($configVariations, $responses);
    }

    private function analyzeGridResults(array $configs, array $responses): array
    {
        $results = [];

        foreach ($responses as $idx => $res) {
            $config = $configs[$idx] ?? [];
            if (!($res['ok'] ?? false)) {
                $results[] = [
                    'config' => $config,
                    'status' => 'FAILED',
                ];
                continue;
            }

            $snapshot = $res['snapshot'] ?? [];
            $stability = $snapshot['stability_index'] ?? 0.0;
            $entropy = $snapshot['entropy'] ?? 1.0;
            $sci = $snapshot['sci'] ?? 0.0;
            
            $status = 'STABLE';
            if ($stability < 0.2) $status = 'COLLAPSE';
            if ($entropy > 0.8) $status = 'HEAT_DEATH';

            $results[] = [
                'config' => $config,
                'status' => $status,
                'metrics' => [
                    'stability' => $stability,
                    'entropy' => $entropy,
                    'sci' => $sci,
                ]
            ];
        }

        return $results;
    }
}
