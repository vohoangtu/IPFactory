<?php
namespace App\Modules\Simulation\Core\Engines\Social;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;

/**
 * GlobalEconomyEngine — Tổng hợp kinh tế toàn cầu: GDP, inflation, trade_balance.
 */
class GlobalEconomyEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string { return 'global_economy'; }
    public function phase(): string { return 'economy'; }
    public function priority(): int { return 20; }
    public function tickRate(): int { return 1; }
    public function isParallelSafe(): bool { return true; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $result = new EngineResult();
        $tick   = $ctx->getTick();

        if ($tick % 20 !== 0) { return $result; }

        $zones  = $state->getZones();
        $market = $state->get('economy.market', []);
        $price  = (float) ($market['food_price'] ?? 1.0);

        $totalProduction = 0.0;
        $totalWealth     = 0.0;
        $totalPopulation = 0.0;

        foreach ($zones as $zone) {
            $s = $zone['state'] ?? [];
            $totalProduction += (float) ($s['resource'] ?? 0);
            $totalWealth     += (float) ($s['wealth'] ?? 0);
            $totalPopulation += (float) ($s['population'] ?? 0);
        }

        $gdp = $totalProduction * $price;
        $prevGdp = (float) $state->get('economy.global.gdp', $gdp);
        $inflation = $prevGdp > 0 ? ($gdp - $prevGdp) / $prevGdp : 0.0;
        $gdpPerCapita = $totalPopulation > 0 ? $gdp / $totalPopulation : 0.0;

        $result->stateChanges[] = ['economy' => array_merge(
            $state->get('economy', []),
            [
                'global' => [
                    'gdp' => round($gdp, 2),
                    'gdp_per_capita' => round($gdpPerCapita, 4),
                    'inflation' => round($inflation, 4),
                    'total_wealth' => round($totalWealth, 2),
                    'total_population' => round($totalPopulation, 2),
                ],
            ]
        )];

        return $result;
    }
}
