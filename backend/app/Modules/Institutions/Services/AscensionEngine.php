<?php

namespace App\Modules\Institutions\Services;

use App\Modules\Simulation\Core\Runtime\Domain\UniverseState;
use App\Modules\Simulation\Core\Runtime\Events\AscensionEvent;
use App\Modules\Simulation\Core\Runtime\Events\EschatonEvent;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use function resource_path;

/**
 * Ascension Engine V2: Pure logic for cosmic-level phase transitions via DSL.
 */
class AscensionEngine
{
    public function __construct(
        protected RuleVmService $ruleVm
    ) {}

    /**
     * Evaluate cosmic-level events based on current universe state.
     * 
     * @return \App\Modules\Simulation\Core\Runtime\Contracts\SimulationEvent[]
     */
    public function evaluate(UniverseState $state): array
    {
        $events = [];
        $dslFile = resource_path('worldos_rules/physics/core.dsl');
        $dsl = @file_get_contents($dslFile) ?: '';

        // Prepare context for transitions
        // We calculate probability in PHP if needed, but DSL can do it too.
        // Let's pass random variables for DSL evaluation.
        $rawState = [
            'entropy' => (float) $state->entropy,
            'order' => (float) $state->order,
            'energyLevel' => (float) $state->energyLevel,
            'collapse_pressure' => (float) ($state->pressures['collapse_pressure'] ?? 0),
            'ascension_pressure' => (float) ($state->pressures['ascension_pressure'] ?? ($state->order * $state->energyLevel)),
            'random_chance' => lcg_value(),
            'ascension_probability' => $this->calculateAscensionProbability(
                (float) ($state->pressures['ascension_pressure'] ?? ($state->order * $state->energyLevel))
            ),
        ];

        $result = $this->ruleVm->evaluateRawState($rawState, $dsl);
        $outputs = $result['outputs'] ?? [];

        foreach ($outputs as $out) {
            if (($out['type'] ?? '') === 'event') {
                $eventName = $out['event_name'] ?? '';
                
                if ($eventName === 'ESCHATON') {
                    $events[] = new EschatonEvent(
                        oldEpoch: (int) $state->epoch,
                        newEpoch: (int) $state->epoch + 1,
                        tick: (int) $state->tick,
                        cause: $out['params']['cause'] ?? 'unknown'
                    );
                }

                if ($eventName === 'ASCENSION') {
                    $events[] = new AscensionEvent(
                        oldLevel: (int) $state->level,
                        newLevel: (int) $state->level + 1,
                        tick: (int) $state->tick
                    );
                }
            }
        }

        return $events;
    }

    /**
     * Sigmoid-based probability curve for natural transition feel.
     * Still kept here as a helper for easier DSL parameter feeding.
     */
    protected function calculateAscensionProbability(float $pressure): float
    {
        $k = 15;
        $x0 = 0.97;
        return 1 / (1 + exp(-$k * ($pressure - $x0)));
    }
}




