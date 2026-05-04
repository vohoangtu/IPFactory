<?php
namespace App\Modules\Simulation\Core\Engines\Social;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use function config;

/**
 * CivilizationSettlementEngine — Settlement growth: camp → village → town → city.
 *
 * Dựa trên population density per zone.
 */
class CivilizationSettlementEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string { return 'civilization_settlement'; }
    public function phase(): string { return 'social'; }
    public function priority(): int { return 53; }
    public function tickRate(): int { return 1; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $result = new EngineResult();
        $tick   = $ctx->getTick();

        if ($tick % 20 !== 0) { return $result; }

        $zones = $state->getZones();
        $updatedZones = [];

        $thresholds = config('worldos.intelligence.civilization_settlement_thresholds', [
            'camp'    => 0,
            'village' => 100,
            'town'    => 1000,
            'city'    => 10000,
            'metropolis' => 100000,
        ]);

        foreach ($zones as $idx => $zone) {
            $s = $zone['state'] ?? [];
            $population = (float) ($s['population'] ?? 0);

            // Determine settlement type
            $settlement = 'camp';
            foreach ($thresholds as $type => $threshold) {
                if ($population >= $threshold) {
                    $settlement = $type;
                }
            }

            // Infrastructure level: scales with settlement
            $infraLevel = match ($settlement) {
                'camp' => 0.1,
                'village' => 0.3,
                'town' => 0.5,
                'city' => 0.8,
                'metropolis' => 1.0,
                default => 0.1,
            };

            // Capacity bonus from settlement
            $capacityBonus = match ($settlement) {
                'city' => 1.5,
                'metropolis' => 2.0,
                default => 1.0,
            };

            $s['settlement_type'] = $settlement;
            $s['infrastructure_level'] = round($infraLevel, 2);
            $s['resource_capacity'] = round(100.0 * $capacityBonus, 2);
            $zone['state'] = $s;
            $updatedZones[] = $zone;
        }

        if (!empty($updatedZones)) {
            $result->stateChanges[] = ['zones' => $updatedZones];
        }

        return $result;
    }
}
