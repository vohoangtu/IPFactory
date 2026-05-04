<?php

namespace App\Modules\Intelligence\Services\Analysis;

use App\Contracts\SimulationEngineClientInterface;

/**
 * Detects strange attractors (chaotic but bounded state trajectories).
 * Relies on the high-performance Rust engine for recurrence matrix calculation.
 */
class StrangeAttractorDetector
{
    public function __construct(
        private SimulationEngineClientInterface $engine
    ) {}

    /**
     * Detect if a trajectory is a strange attractor.
     *
     * @param array $points  The trajectory points {tick, state}.
     * @param float $recurrenceThreshold  Sensitivity for recurrence detection.
     * @return array The analysis results from the engine.
     */
    public function detect(array $points, float $recurrenceThreshold = 0.1): array
    {
        if (count($points) < 10) {
            return [
                'ok' => false,
                'is_strange_attractor' => false,
                'error_message' => 'Not enough points for analysis.',
            ];
        }

        // Call the Rust engine's heavy analysis RPC
        $result = $this->engine->analyzeTrajectory($points, $recurrenceThreshold);

        if (!($result['ok'] ?? true)) {
            return $result;
        }

        // A "Strange Attractor" is defined in this system as:
        // 1. Bounded: Trajectory stays within a finite region.
        // 2. Chaotic: Positive (or near-zero) Lyapunov exponent.
        // 3. Structured: High recurrence rate (not purely random) and not converged.
        $isStrange = $result['is_bounded'] 
            && $result['is_recurrent'] 
            && ($result['max_lyapunov_estimate'] > -0.01);

        return array_merge($result, [
            'is_strange_attractor' => $isStrange,
        ]);
    }
}
