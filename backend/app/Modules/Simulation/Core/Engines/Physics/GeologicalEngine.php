<?php
namespace App\Modules\Simulation\Core\Engines\Physics;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\WorldKernel;
use App\Modules\Simulation\Core\Events\WorldEvent;
use App\Modules\Simulation\Core\Events\WorldEventType;
use function config;


/**
 * GeologicalEngine — Kiến tạo địa chất, khoáng sản, địa hình.
 *
 * Chạy mỗi N tick (config: geological.tick_interval).
 * Output: zone.state.elevation, zone.state.terrain_type, zone.state.minerals
 */
class GeologicalEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string { return 'geological'; }
    public function phase(): string { return WorldKernel::PHASE_ENVIRONMENT; }
    public function priority(): int { return 3; }
    public function tickRate(): int { return 1; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $result   = new EngineResult();
        $tick     = $ctx->getTick();
        $interval = (int) config('worldos.geological.tick_interval', 1);

        if ($interval <= 0 || $tick % $interval !== 0) {
            return $result;
        }

        $zones   = $state->getZones();
        $seed    = $ctx->getSeed();
        $driftRate = (float) config('worldos.geological.elevation_drift_rate', 0.002);
        $volcanoProb = (float) config('worldos.geological.volcano_probability_per_zone', 0.02);
        $erosionRate = (float) config('worldos.geological.erosion_rate', 0.001);

        $updatedZones = [];

        foreach ($zones as $idx => $zone) {
            $zoneId    = (int) ($zone['id'] ?? $idx);
            $zoneState = $zone['state'] ?? [];
            $elevation = (float) ($zoneState['elevation'] ?? 0.5);

            // Seeded deterministic drift
            $hash = crc32("geo_{$seed}_{$zoneId}_{$tick}");
            $drift = (($hash % 2001) - 1000) / 1000.0; // -1.0 .. 1.0
            $elevation += $driftRate * $drift;
            $elevation -= $erosionRate; // erosion luôn kéo xuống
            $elevation = max(0.0, min(1.0, $elevation));

            // Terrain type dựa trên elevation
            $terrainType = match (true) {
                $elevation < 0.15 => 'ocean',
                $elevation < 0.3  => 'plains',
                $elevation < 0.5  => 'hills',
                $elevation < 0.7  => 'highlands',
                $elevation < 0.85 => 'mountains',
                default           => 'peaks',
            };

            // Khoáng sản: richness tỉ lệ thuận elevation (núi nhiều khoáng sản hơn)
            $mineralHash = crc32("mineral_{$seed}_{$zoneId}");
            $mineralRichness = max(0.0, min(1.0, $elevation * 0.6 + abs($mineralHash % 1000) / 2500.0));

            // Núi lửa
            $volcanoHash = abs(crc32("volcano_{$seed}_{$zoneId}_{$tick}")) / 2147483647;
            if ($volcanoHash < $volcanoProb && $elevation > 0.5) {
                $result->events[] = WorldEvent::create(
                    type: WorldEventType::ECOLOGICAL_COLLAPSE,
                    universeId: $ctx->getUniverseId(),
                    tick: $tick,
                    payload: ['type' => 'VOLCANIC_ERUPTION', 'zone_id' => $zoneId],
                    impactScore: 0.7
                );
                // Núi lửa tăng mineral, giảm population (handled bởi engine khác qua event)
                $mineralRichness = min(1.0, $mineralRichness + 0.15);
            }

            $zoneState['elevation']        = round($elevation, 4);
            $zoneState['terrain_type']     = $terrainType;
            $zoneState['mineral_richness'] = round($mineralRichness, 4);
            $zone['state'] = $zoneState;
            $updatedZones[] = $zone;
        }

        if (!empty($updatedZones)) {
            $state->set('zones', $updatedZones);
            $result->stateChanges[] = ['zones' => $updatedZones];
        }

        return $result;
    }
}
