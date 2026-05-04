<?php

namespace App\Modules\Intelligence\Services\Analysis;

use App\Contracts\SimulationEngineClientInterface;

/**
 * Detects "Dark Attractors": stable but pathological stagnation traps.
 * (e.g., Oligarchy lock, permanent conflict, or innovation-stagnation)
 */
class DarkAttractorDetector
{
    public function __construct(
        private SimulationEngineClientInterface $engine
    ) {}

    /**
     * Detect if a state is a dark attractor by running perturbation tests.
     *
     * @param int $universeId
     * @param array $currentState  The state vector to test.
     * @param array|null $worldConfig
     * @param int $perturbations   Number of random perturbations to test.
     * @param int $ticksPerTest    Length of each test simulation.
     * @return array{is_dark_attractor: bool, stability_ratio: float, trap_type: string}
     */
    public function detect(
        int $universeId, 
        array $currentState, 
        ?array $worldConfig = null,
        int $perturbations = 5,
        int $ticksPerTest = 20
    ): array {
        $requests = [];
        
        // 1. Prepare N perturbed states
        for ($i = 0; $i < $perturbations; $i++) {
            $perturbedState = $this->applyNoise($currentState, 0.05); // 5% noise
            $requests[] = [
                'universe_id' => $universeId,
                'ticks' => $ticksPerTest,
                'state_input' => $perturbedState,
                'world_config' => $worldConfig,
            ];
        }

        // 2. Batch simulate to see if they converge back to the same zone
        $batchResult = $this->engine->batchAdvance($requests);
        $responses = $batchResult['responses'] ?? [];

        if (empty($responses)) {
            return [
                'is_dark_attractor' => false,
                'stability_ratio' => 0.0,
                'trap_type' => 'unknown',
                'error' => 'Batch simulation failed.',
            ];
        }

        // 3. Measure convergence (how many end states are close to current or converged to a trap)
        $returns = 0;
        foreach ($responses as $res) {
            $endState = $res['snapshot']['state_vector'] ?? [];
            if ($this->isTrapped($currentState, $endState)) {
                $returns++;
            }
        }

        $ratio = $returns / count($responses);
        $isTrapped = $ratio > 0.8; // High stability in the trap region

        $type = 'unknown';
        if ($isTrapped) {
            $type = $this->classifyTrap($currentState);
        }

        return [
            'is_dark_attractor' => $isTrapped,
            'stability_ratio' => $ratio,
            'trap_type' => $type,
        ];
    }

    /**
     * Classified defined trap types based on state vector.
     */
    private function classifyTrap(array $state): string
    {
        $tech = $state['knowledge'] ?? 0.0;
        $stability = $state['stability'] ?? 0.0;
        $inequality = $state['coercion'] ?? 0.0; // surrogate for inequality in WORLDOS_V6
        
        if ($tech > 0.8 && $stability > 0.8 && $inequality > 0.7) {
            return 'Oligarchy Lock';
        }

        if ($tech < 0.2 && $stability > 0.8) {
            return 'Stagnation Trap';
        }

        if ($stability < 0.3) {
            return 'Permanent Conflict';
        }

        return 'Generic Trap';
    }

    /**
     * Check if the end state is close enough to the trap region.
     */
    private function isTrapped(array $baseline, array $current): bool
    {
        // For now, simple Euclidean distance check
        $dist = 0.0;
        $keys = ['knowledge', 'stability', 'coercion', 'entropy'];
        $count = 0;

        foreach ($keys as $k) {
            if (isset($baseline[$k], $current[$k])) {
                $dist += pow($baseline[$k] - $current[$k], 2);
                $count++;
            }
        }

        if ($count === 0) return false;
        return sqrt($dist / $count) < 0.05; // 5% average deviation
    }

    /**
     * Apply random noise to a state vector.
     */
    private function applyNoise(array $state, float $magnitude): array
    {
        $newState = $state;
        foreach ($newState as $k => $v) {
            if (is_numeric($v)) {
                $newState[$k] = max(0, min(1, $v + (mt_rand(-100, 100) / 100 * $magnitude)));
            }
        }
        return $newState;
    }
}
