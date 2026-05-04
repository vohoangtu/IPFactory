<?php
namespace App\Modules\Simulation\Core\Engines\Physics;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Events\WorldEvent;
use App\Modules\Simulation\Core\Events\WorldEventType;

/**
 * CosmicPressureEngine — Áp lực vũ trụ tăng dần theo thời gian.
 *
 * cosmic_pressure ảnh hưởng đến zone stability và entropy tendency.
 * Nếu pressure > 0.9 → emit PHASE_TRANSITION event (cosmic stress).
 */
class CosmicPressureEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string { return 'cosmic_pressure'; }
    public function phase(): string { return 'physical'; }
    public function priority(): int { return 1; }
    public function tickRate(): int { return 1; }
    public function isParallelSafe(): bool { return true; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $result = new EngineResult();
        $tick    = $ctx->getTick();
        $entropy = (float) $state->get('entropy', 0.0);

        $dimensionalStability = (float) ($state->get('reality_constants.dimensional_stability', 0.8));

        // Áp lực tăng logarit theo tick, nhân entropy
        $ageFactor = log1p($tick) / 15.0;
        $cosmicPressure = min(1.0, $entropy * 0.4 + $ageFactor * 0.3 + (1.0 - $dimensionalStability) * 0.3);

        $result->stateChanges[] = ['cosmic_pressure' => round($cosmicPressure, 4)];

        // Event khi áp lực quá cao
        if ($cosmicPressure > 0.9) {
            $result->events[] = WorldEvent::create(
                type: WorldEventType::PHASE_TRANSITION,
                universeId: $ctx->getUniverseId(),
                tick: $tick,
                payload: ['type' => 'COSMIC_STRESS', 'pressure' => $cosmicPressure],
                impactScore: $cosmicPressure
            );
        }

        return $result;
    }
}
