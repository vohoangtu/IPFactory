<?php
declare(strict_types=1);

namespace App\Modules\Simulation\Core\Domain;

use App\Modules\Simulation\Core\Runtime\State\WorldState;

/**
 * Result of a WorldKernel::runTick() call.
 * Contains the final state and any events/causal links emitted by engines.
 */
final class SimulationTickResult
{
    public function __construct(
        public readonly WorldState $state,
        /** @var array<array|object> */
        public readonly array $events = [],
        /** @var array<string, int> */
        public readonly array $causalLinks = [],
        /** @var EngineExecutionRecord[] */
        public readonly array $engineMetrics = [],
    ) {
    }
}
