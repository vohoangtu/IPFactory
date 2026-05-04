<?php

namespace App\Modules\Intelligence\Services\Analysis;

/**
 * Records simulation trajectories (state vectors over time) for analysis.
 * Used to detect strange attractors and regime loops.
 */
class TrajectoryRecorder
{
    private array $history = [];

    /**
     * Clear the recorder.
     */
    public function clear(): void
    {
        $this->history = [];
    }

    /**
     * Record a snapshot's state.
     *
     * @param int $tick
     * @param array $stateVector  The raw state vector from the engine.
     * @param string|null $winner Optional archetype ID of the current winner.
     * @param array $scores      Optional distribution of scores.
     */
    public function record(int $tick, array $stateVector, ?string $winner = null, array $scores = []): void
    {
        $this->history[] = [
            'tick' => $tick,
            'state' => $stateVector,
            'winner' => $winner,
            'scores' => $scores,
        ];
    }

    /**
     * Get the full trajectory history.
     *
     * @return array
     */
    public function getHistory(): array
    {
        return $this->history;
    }

    /**
     * Extract a flattened trajectory for Rust AnalyzeTrajectory.
     * Filters for specific dimensions if needed.
     *
     * @param array|null $dimensions Keys to include in the flattened vector.
     * @return array Array of {tick, state} points.
     */
    public function getFlattenedTrajectory(?array $dimensions = null): array
    {
        return array_map(function ($entry) use ($dimensions) {
            $state = [];
            if ($dimensions === null) {
                // If no dimensions specified, we take all numeric values
                foreach ($entry['state'] as $val) {
                    if (is_numeric($val)) {
                        $state[] = (float) $val;
                    }
                }
            } else {
                foreach ($dimensions as $dim) {
                    $state[] = (float) ($entry['state'][$dim] ?? 0.0);
                }
            }

            return [
                'tick' => $entry['tick'],
                'state' => $state,
            ];
        }, $this->history);
    }

    /**
     * Get the sequence of regime winners.
     *
     * @return array List of winner IDs.
     */
    public function getRegimeSequence(): array
    {
        return array_column($this->history, 'winner');
    }
}
