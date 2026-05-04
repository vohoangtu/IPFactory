<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Core\Engines\Social;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Runtime\State\WorldState;

/**
 * ProductionChainEngine — Industrial output per zone with material bonus.
 *
 * industrial_output = economy_surplus * material_bonus_multiplier * 0.5
 * material_bonus_multiplier = 1.0 + (total_material_bonus_count * 0.1)
 * Output: civilization.production = { zones: [{industrial_output}], total_industrial_output, material_bonus_multiplier }
 */
class ProductionChainEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    private const EFFICIENCY_FACTOR = 0.5;

    private const MATERIAL_BONUS_RATE = 0.1;

    public function name(): string
    {
        return 'ProductionChainEngine';
    }

    public function phase(): string
    {
        return 'economy';
    }

    public function priority(): int
    {
        return 26;
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

        // Calculate total material bonus count across all zones
        $totalMaterialBonusCount = 0;
        foreach ($zones as $zone) {
            $s = $zone['state'] ?? [];
            $totalMaterialBonusCount += (int) ($s['material_bonus_count'] ?? 0);
        }

        $materialBonusMultiplier = 1.0 + ($totalMaterialBonusCount * self::MATERIAL_BONUS_RATE);

        $totalIndustrialOutput = 0.0;
        $zoneProduction = [];

        foreach ($zones as $index => $zone) {
            $s = $zone['state'] ?? [];
            $surplus = (float) ($s['economy_surplus'] ?? 0);

            $industrialOutput = $surplus * $materialBonusMultiplier * self::EFFICIENCY_FACTOR;

            $zoneProduction[$index] = [
                'industrial_output' => $industrialOutput,
            ];

            $totalIndustrialOutput += $industrialOutput;
        }

        $result = new EngineResult();
        $result->stateChanges[] = [
            'civilization.production' => [
                'zones' => $zoneProduction,
                'total_industrial_output' => $totalIndustrialOutput,
                'material_bonus_multiplier' => $materialBonusMultiplier,
            ],
        ];

        return $result;
    }
}
