<?php
namespace App\Modules\Simulation\Core\Engines\Social;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;

/**
 * CivilizationPhysicsEngine — Hạ tầng, đô thị hóa, suy thoái.
 *
 * Infrastructure decay theo thời gian, tăng áp lực khi population > capacity.
 */
class CivilizationPhysicsEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string { return 'civilization_physics'; }
    public function phase(): string { return 'social'; }
    public function priority(): int { return 50; }
    public function tickRate(): int { return 1; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $result = new EngineResult();
        $tick   = $ctx->getTick();

        if ($tick % 30 !== 0) { return $result; }

        $zones = $state->getZones();
        $updatedZones = [];

        foreach ($zones as $idx => $zone) {
            $s = $zone['state'] ?? [];
            $infraLevel  = (float) ($s['infrastructure_level'] ?? 0.1);
            $population  = (float) ($s['population'] ?? 0);
            $capacity    = (float) ($s['resource_capacity'] ?? 100.0);

            // Decay: infrastructure decays naturally
            $decayRate = 0.01;
            $infraLevel -= $decayRate;

            // Overcrowding penalty
            $popCapRatio = $capacity > 0 ? $population / $capacity : 1.0;
            if ($popCapRatio > 0.8) {
                $overcrowdingPenalty = ($popCapRatio - 0.8) * 0.1;
                $infraLevel -= $overcrowdingPenalty;
            }

            // Urban density
            $urbanDensity = min(1.0, $population / max(1, $capacity));

            $infraLevel = max(0.0, min(1.0, $infraLevel));

            $s['infrastructure_health'] = round($infraLevel, 4);
            $s['urban_density'] = round($urbanDensity, 4);
            $zone['state'] = $s;
            $updatedZones[] = $zone;
        }

        if (!empty($updatedZones)) {
            $result->stateChanges[] = ['zones' => $updatedZones];
        }

        return $result;
    }
}
