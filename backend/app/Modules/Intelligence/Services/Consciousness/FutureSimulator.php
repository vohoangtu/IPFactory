<?php

namespace App\Modules\Intelligence\Services\Consciousness;

use App\Contracts\SimulationEngineClientInterface;

/**
 * Simulates multiple "What-if" futures for the civilization.
 * Used for risk prediction and strategic planning.
 */
class FutureSimulator
{
    public function __construct(
        private SimulationEngineClientInterface $engine
    ) {}

    /**
     * Predict possible futures for a given SelfModel.
     * 
     * @param SelfModel $model
     * @param int $scenarios
     * @param int $horizon How many ticks into the future.
     * @return array List of predicted snapshots.
     */
    public function predict(SelfModel $model, int $scenarios = 5, int $horizon = 50): array
    {
        $requests = [];
        
        // We simulate several scenarios with slight noise to see variance
        for ($i = 0; $i < $scenarios; $i++) {
            $requests[] = [
                'universe_id' => 0,
                'ticks' => $horizon,
                'state_input' => $model->currentState,
                'world_config' => [
                    'noise' => 0.02 * ($i + 1), // increasing variance
                ],
            ];
        }

        $batchResult = $this->engine->batchAdvance($requests);
        return $batchResult['responses'] ?? [];
    }

    /**
     * Analyze predicted futures for existential risks.
     */
    public function analyzeRisks(array $futures): array
    {
        $risks = [];
        foreach ($futures as $f) {
            $snapshot = $f['snapshot'] ?? [];
            $stability = $snapshot['stability_index'] ?? 0.5;
            $entropy = $snapshot['entropy'] ?? 0.5;

            if ($stability < 0.2) $risks[] = "Collapse Risk Detected";
            if ($entropy > 0.8) $risks[] = "Heat Death / Absolute Chaos Risk";
        }

        return array_unique($risks);
    }
}
