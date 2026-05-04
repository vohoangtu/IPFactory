<?php

namespace App\Modules\Simulation\Core\Contracts;

use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;

interface SimulationEngine
{
    /** Human-readable engine name (e.g. for logging). */
    public function name(): string;

    /**
     * Semantic version for deterministic replay (e.g. "1.0.0"). Doc §26.
     */
    public function version(): string;

    /**
     * Priority in tick pipeline (1 = first). Doc §3: Planet=1, Climate=2, Ecology=3, Civilization=4, …
     */
    public function priority(): int;

    /**
     * Phase group for scaling (Doc 21 §6). E.g. PHYSICAL, CLIMATE, ECOLOGY, ECONOMY, SOCIAL, POLITICS, CONFLICT, CULTURE, META. Use 'default' if not using groups.
     */
    public function phase(): string;

    /**
     * Run this engine every N ticks. 1 = every tick, 10 = every 10th tick. Doc 21 §9: same as "interval" (ticks between runs).
     */
    public function tickRate(): int;

    /**
     * Evaluate current state and return result (events, state changes, metrics). Must not mutate DB or snapshot.
     */
    public function handle(WorldState $state, TickContext $ctx): EngineResult;

    /**
     * Phase 4: Parallel Execution Flag.
     * If true, this engine may be run concurrently with other parallel-safe engines in the same phase group.
     * Requirements: must NOT mutate shared in-process state (no static writes, no DB writes during handle).
     * Returns false by default for safety — opt in by overriding to true.
     */
    public function isParallelSafe(): bool;

    /**
     * Phase 6: Priority Category for Auto-Scaling.
     * Expected returns: 'CRITICAL', 'STOCHASTIC', or 'COSMETIC'.
     * CRITICAL engines always run. STOCHASTIC and COSMETIC are dropped under heavy load.
     */
    public function priorityCategory(): string;
}
