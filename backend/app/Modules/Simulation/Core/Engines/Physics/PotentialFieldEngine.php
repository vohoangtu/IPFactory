<?php

namespace App\Modules\Simulation\Core\Engines\Physics;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Effects\ZoneFieldUpdateEffect;
use App\Modules\Simulation\Core\Events\WorldEvent;
use App\Modules\Simulation\Core\Events\WorldEventType;
use App\Modules\Simulation\Core\Services\TopologyResolver;
use App\Modules\Simulation\Core\Engines\Physics\ZonePressureCalculator;
use App\Modules\Simulation\Core\Support\SimulationRandom;

/**
 * Zone-level Potential Field: compute → decay → diffuse → couple → write zone pressures.
 * Dual topology: uses zone['neighbors'] when present, else ring. Runs before ZoneConflictEngine.
 */
final class PotentialFieldEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    public function phase(): string
    {
        return 'physical';
    }

    private const DECAY = 0.97;
    private const DIFFUSION_RATE = 0.1;

    public function __construct(
        private readonly ZonePressureCalculator $calculator,
        private readonly TopologyResolver $topology,
    ) {
    }

    public function name(): string
    {
        return 'potential_field';
    }

    public function priority(): int
    {
        return 1;
    }

    public function tickRate(): int
    {
        return max(1, (int) (config('worldos.time_scale_factors.potential_field') ?? 1));
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $rng = new SimulationRandom($ctx->getSeed(), $ctx->getTick(), 0);
        $effects = $this->evaluate($state, $rng);
        $events = [];
        if ($effects !== []) {
            $zonesCount = count($state->getZones());
            $events[] = WorldEvent::create(
                WorldEventType::ZONE_PRESSURES_UPDATED,
                $ctx->getUniverseId(),
                $ctx->getTick(),
                null,
                [],
                0.15,
                [],
                ['zones_count' => $zonesCount]
            );
        }
        return new EngineResult($events, $effects, []);
    }

    /**
     * Purification: Calculation logic migrated to Rust core.
     * This method now acts as a passive observer/bridge.
     * 
     * @return \App\Modules\Simulation\Core\Contracts\Effect[]
     */
    private function evaluate(WorldState $state, SimulationRandom $rng): array
    {
        // Physics pressures are now handled by potential_fields.rs in Rust.
        // We return empty effects as the state is already updated via gRPC.
        return [];
    }
}

