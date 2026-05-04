<?php

namespace App\Modules\Simulation\Core\Concerns;

/**
 * Doc 21 §6: Default phase group 'default' for engines that do not yet use phase groups.
 */
trait DefaultSimulationEnginePhase
{
    use HasEngineVersion;

    public function phase(): string
    {
        return 'default';
    }

    /**
     * Phase 4: Default to sequential execution for safety.
     * Override to return true only in truly stateless, read-only engines.
     */
    public function isParallelSafe(): bool
    {
        return false;
    }

    /**
     * Phase 6: Default priority category. Assumes STOCHASTIC unless explicitly set to CRITICAL or COSMETIC.
     */
    public function priorityCategory(): string
    {
        return 'STOCHASTIC';
    }
}
