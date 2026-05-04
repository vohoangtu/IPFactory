<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Core\Engines\Social;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Runtime\State\WorldState;

/**
 * FinanceEngine — Zone-level credit/debt tracking.
 *
 * For each zone: net = economy_surplus - economy_consumption.
 * Positive net → credit; negative net → debt.
 * Output: civilization.finance = { zones: [{credit, debt}], total_credit, total_debt }
 */
class FinanceEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string
    {
        return 'FinanceEngine';
    }

    public function phase(): string
    {
        return 'economy';
    }

    public function priority(): int
    {
        return 25;
    }

    public function tickRate(): int
    {
        return 1;
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $zones = $state->getZones();

        if (empty($zones)) {
            return EngineResult::empty();
        }

        $totalCredit = 0.0;
        $totalDebt = 0.0;
        $zoneFinance = [];

        foreach ($zones as $index => $zone) {
            $s = $zone['state'] ?? [];
            $surplus = (float) ($s['economy_surplus'] ?? 0);
            $consumption = (float) ($s['economy_consumption'] ?? 0);

            $net = $surplus - $consumption;
            $credit = max(0.0, $net);
            $debt = max(0.0, -$net);

            $zoneFinance[$index] = [
                'credit' => $credit,
                'debt' => $debt,
            ];

            $totalCredit += $credit;
            $totalDebt += $debt;
        }

        $result = new EngineResult();
        $result->stateChanges[] = [
            'civilization.finance' => [
                'zones' => $zoneFinance,
                'total_credit' => $totalCredit,
                'total_debt' => $totalDebt,
            ],
        ];

        return $result;
    }
}
