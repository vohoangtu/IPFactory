<?php
namespace App\Modules\Simulation\Core\Engines\Social;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Events\WorldEvent;
use App\Modules\Simulation\Core\Events\WorldEventType;
use function config;


/**
 * PoliticsEngine — Governance type emergence.
 *
 * tribal → chiefdom → monarchy → republic dựa trên population, tech, inequality.
 */
class PoliticsEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string { return 'politics'; }
    public function phase(): string { return 'politics'; }
    public function priority(): int { return 10; }
    public function tickRate(): int { return 1; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $result   = new EngineResult();
        $tick     = $ctx->getTick();
        $interval = (int) config('worldos.politics_tick_interval', 25);

        if ($interval <= 0 || $tick % $interval !== 0) { return $result; }

        $zones     = $state->getZones();
        $techLevel = (float) $state->get('tech_level', 0.1);
        $gini      = (float) $state->get('economy.gini', 0.3);

        $totalPop    = 0.0;
        foreach ($zones as $zone) {
            $totalPop += (float) ($zone['state']['population'] ?? 0);
        }

        $prevGovernance = $state->get('politics.governance_type', 'tribal');

        // Governance emergence logic
        $governance = match (true) {
            $totalPop < 50                        => 'tribal',
            $totalPop < 200                       => 'chiefdom',
            $techLevel > 0.5 && $gini > 0.6       => 'republic',
            $techLevel > 0.3                      => 'monarchy',
            $totalPop < 500                       => 'chiefdom',
            default                               => 'monarchy',
        };

        // Political stability: higher with legitimacy, lower with inequality
        $stability = max(0.0, min(1.0, 0.7 - $gini * 0.4 + $techLevel * 0.2));

        $result->stateChanges[] = ['politics' => [
            'governance_type' => $governance,
            'stability' => round($stability, 4),
            'total_population' => round($totalPop, 2),
        ]];

        if ($governance !== $prevGovernance) {
            $result->events[] = WorldEvent::create(
                type: WorldEventType::POLITICAL_REVOLUTION,
                universeId: $ctx->getUniverseId(),
                tick: $tick,
                payload: [
                    'from' => $prevGovernance,
                    'to' => $governance,
                    'trigger' => $gini > 0.6 ? 'inequality' : 'population_growth',
                ],
                impactScore: 0.8
            );
        }

        return $result;
    }
}
