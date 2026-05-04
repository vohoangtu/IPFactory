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
 * TradeEngine — Trao đổi liên vùng: surplus → deficit.
 *
 * Zone có dư resource gửi tới zone thiếu (neighbor hoặc global).
 */
class TradeEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string { return 'trade'; }
    public function phase(): string { return 'economy'; }
    public function priority(): int { return 22; }
    public function tickRate(): int { return 1; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $result = new EngineResult();
        $tick   = $ctx->getTick();

        if ($tick % 15 !== 0) { return $result; }

        $zones = $state->getZones();
        $tradeEfficiency = (float) config('worldos.trade.efficiency', 0.6);

        $surplusZones = [];
        $deficitZones = [];

        foreach ($zones as $idx => $zone) {
            $s = $zone['state'] ?? [];
            $resource   = (float) ($s['resource'] ?? 0);
            $population = (float) ($s['population'] ?? 0);
            $need       = $population * 2.0;
            $balance    = $resource - $need;

            if ($balance > 5.0) {
                $surplusZones[$idx] = $balance;
            } elseif ($balance < -2.0) {
                $deficitZones[$idx] = abs($balance);
            }
        }

        if (empty($surplusZones) || empty($deficitZones)) {
            return $result;
        }

        $updatedZones = $zones;
        $totalTradeVolume = 0.0;

        foreach ($deficitZones as $dIdx => $deficit) {
            foreach ($surplusZones as $sIdx => $surplus) {
                if ($surplus <= 0) continue;

                $tradeAmount = min($surplus, $deficit) * $tradeEfficiency;
                if ($tradeAmount < 0.5) continue;

                $dState = $updatedZones[$dIdx]['state'];
                $sState = $updatedZones[$sIdx]['state'];

                $sState['resource'] = round($sState['resource'] - $tradeAmount, 2);
                $dState['resource'] = round($dState['resource'] + $tradeAmount, 2);

                // Wealth transfer: exporter gains gold
                $sState['wealth'] = round(($sState['wealth'] ?? 0) + $tradeAmount * 0.1, 2);
                $sState['trade_balance'] = round(($sState['trade_balance'] ?? 0) + $tradeAmount, 2);
                $dState['trade_balance'] = round(($dState['trade_balance'] ?? 0) - $tradeAmount, 2);

                $updatedZones[$dIdx]['state'] = $dState;
                $updatedZones[$sIdx]['state'] = $sState;

                $surplusZones[$sIdx] -= $tradeAmount;
                $totalTradeVolume += $tradeAmount;
                $deficit -= $tradeAmount;
                if ($deficit <= 0) break;
            }
        }

        if ($totalTradeVolume > 0) {
            $result->stateChanges[] = ['zones' => array_values($updatedZones)];

            if ($totalTradeVolume > 20.0) {
                $result->events[] = WorldEvent::create(
                    type: WorldEventType::TRADE_ROUTE_ESTABLISHED,
                    universeId: $ctx->getUniverseId(),
                    tick: $tick,
                    payload: ['total_volume' => round($totalTradeVolume, 2)],
                    impactScore: 0.4
                );
            }
        }

        return $result;
    }
}
