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
 * MarketEngine — Supply/demand, giá cả thị trường.
 *
 * Tính giá từ cung/cầu. MARKET_CRASH khi giá quá thấp, ECONOMIC_BOOM khi surplus lớn.
 */
class MarketEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string { return 'market'; }
    public function phase(): string { return 'economy'; }
    public function priority(): int { return 21; }
    public function tickRate(): int { return 1; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $result = new EngineResult();
        $tick   = $ctx->getTick();

        if ($tick % 10 !== 0) { return $result; }

        $zones    = $state->getZones();
        $basePrice     = (float) config('worldos.market.price_base_food', 1.0);
        $crashThreshold = (float) config('worldos.market.crash_price_threshold', 0.3);
        $boomThreshold  = (float) config('worldos.market.boom_surplus_threshold', 50.0);

        $totalSupply = 0.0;
        $totalDemand = 0.0;

        foreach ($zones as $zone) {
            $s = $zone['state'] ?? [];
            $totalSupply += (float) ($s['resource'] ?? 0);
            $totalDemand += (float) ($s['population'] ?? 0) * 2.0;
        }

        $price = $totalSupply > 0.01
            ? $basePrice * ($totalDemand / $totalSupply)
            : $basePrice * 10.0;

        $price = max(0.1, min(10.0, $price));
        $surplus = $totalSupply - $totalDemand;

        $result->stateChanges[] = ['economy' => [
            'market' => [
                'food_price' => round($price, 4),
                'total_supply' => round($totalSupply, 2),
                'total_demand' => round($totalDemand, 2),
                'surplus' => round($surplus, 2),
            ],
        ]];

        if ($price < $crashThreshold) {
            $result->events[] = WorldEvent::create(
                type: WorldEventType::MARKET_CRASH,
                universeId: $ctx->getUniverseId(),
                tick: $tick,
                payload: ['price' => $price, 'surplus' => $surplus],
                impactScore: 0.8
            );
        }

        if ($surplus > $boomThreshold) {
            $result->events[] = WorldEvent::create(
                type: WorldEventType::ECONOMIC_BOOM,
                universeId: $ctx->getUniverseId(),
                tick: $tick,
                payload: ['surplus' => $surplus, 'price' => $price],
                impactScore: 0.5
            );
        }

        return $result;
    }
}
