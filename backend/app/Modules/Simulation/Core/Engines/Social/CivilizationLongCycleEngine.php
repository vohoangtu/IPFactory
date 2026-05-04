<?php
namespace App\Modules\Simulation\Core\Engines\Social;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Events\WorldEvent;
use App\Modules\Simulation\Core\Events\WorldEventType;

/**
 * CivilizationLongCycleEngine — Chu kỳ văn minh dài hạn.
 *
 * Track prosperity index. Phase: RISING → GOLDEN_AGE → DECLINE → COLLAPSE.
 */
class CivilizationLongCycleEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string { return 'civilization_long_cycle'; }
    public function phase(): string { return 'social'; }
    public function priority(): int { return 52; }
    public function tickRate(): int { return 1; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $result = new EngineResult();
        $tick   = $ctx->getTick();

        if ($tick % 50 !== 0) { return $result; }

        $gdp       = (float) $state->get('economy.global.gdp', 0.0);
        $stability = (float) $state->get('politics.stability', 0.5);
        $legitimacy = (float) $state->get('politics.legitimacy', 0.5);
        $gini      = (float) $state->get('economy.gini', 0.3);

        // Prosperity index: composite of economy + stability + legitimacy - inequality
        $prosperity = ($gdp > 0 ? min(1.0, $gdp / 1000.0) : 0.0) * 0.3
            + $stability * 0.25
            + $legitimacy * 0.25
            - $gini * 0.2;
        $prosperity = max(0.0, min(1.0, $prosperity));

        $prevProsperity = (float) $state->get('civilization.prosperity_index', 0.5);
        $trend = $prosperity - $prevProsperity;

        $prevPhase = $state->get('civilization.phase', 'RISING');

        // Phase determination
        $phase = match (true) {
            $prosperity < 0.2                => 'COLLAPSE',
            $trend < -0.05                   => 'DECLINE',
            $prosperity > 0.7 && $trend >= 0 => 'GOLDEN_AGE',
            $trend > 0.02                    => 'RISING',
            default                          => $prevPhase,
        };

        $result->stateChanges['civilization'] = [
            'phase' => $phase,
            'prosperity_index' => round($prosperity, 4),
            'prosperity_trend' => round($trend, 4),
        ];

        if ($phase !== $prevPhase) {
            $result->events[] = WorldEvent::create(
                type: WorldEventType::PHASE_TRANSITION,
                universeId: $ctx->getUniverseId(),
                tick: $tick,
                payload: [
                    'from' => $prevPhase,
                    'to' => $phase,
                    'prosperity' => round($prosperity, 4),
                ],
                impactScore: 0.7
            );
        }

        return $result;
    }
}
