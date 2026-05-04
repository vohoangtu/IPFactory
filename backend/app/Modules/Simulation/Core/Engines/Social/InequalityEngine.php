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
 * InequalityEngine — Hệ số Gini, phân hóa giàu nghèo giữa zones.
 *
 * Emit STABILITY_CRISIS khi bất bình đẳng quá cao.
 */
class InequalityEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string { return 'inequality'; }
    public function phase(): string { return 'social'; }
    public function priority(): int { return 30; }
    public function tickRate(): int { return 1; }
    public function isParallelSafe(): bool { return true; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $result = new EngineResult();
        $tick   = $ctx->getTick();

        if ($tick % 25 !== 0) { return $result; }

        $zones  = $state->getZones();
        $wealthValues = [];

        foreach ($zones as $zone) {
            $s = $zone['state'] ?? [];
            $wealthValues[] = (float) ($s['wealth'] ?? 0) + (float) ($s['resource'] ?? 0);
        }

        $n = count($wealthValues);
        if ($n < 2) { return $result; }

        // Gini coefficient
        $gini = $this->computeGini($wealthValues);

        $result->stateChanges[] = ['economy' => array_merge(
            $state->get('economy', []),
            [
                'gini' => round($gini, 4),
                'inequality_index' => round($gini * 100, 2),
            ]
        )];

        if ($gini > 0.7) {
            $result->events[] = WorldEvent::create(
                type: WorldEventType::STABILITY_CRISIS,
                universeId: $ctx->getUniverseId(),
                tick: $tick,
                payload: ['gini' => round($gini, 4), 'trigger' => 'extreme_inequality'],
                impactScore: $gini
            );
        }

        return $result;
    }

    private function computeGini(array $values): float
    {
        $n = count($values);
        $mean = array_sum($values) / max(1, $n);
        if ($mean <= 0) return 0.0;

        $sumDiff = 0.0;
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $sumDiff += abs($values[$i] - $values[$j]);
            }
        }

        return $sumDiff / (2 * $n * $n * $mean);
    }
}
