<?php
namespace App\Modules\Simulation\Core\Engines\Physics;

use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Engines\EngineInterface;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use function config;

/**
 * ClimateEngine — Nhiệt độ, mưa, mùa theo zone.
 *
 * Chạy mỗi N tick (config: planetary_climate.tick_interval).
 * Phụ thuộc: GeologicalEngine (elevation).
 * Output: zone.state.temperature, zone.state.rainfall, zone.state.biome
 */
class ClimateEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string { return 'climate'; }
    public function phase(): string { return \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_ENVIRONMENT; }
    public function priority(): int { return 2; }
    public function tickRate(): int { return 1; }
    public function isParallelSafe(): bool { return true; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $result   = new EngineResult();
        $tick     = $ctx->getTick();
        $interval = (int) config('worldos.planetary_climate.tick_interval', 1);

        if ($interval <= 0 || $tick % $interval !== 0) {
            return $result;
        }

        $zones         = $state->getZones();
        $seed          = $ctx->getSeed();
        $baseTemp      = (float) config('worldos.planetary_climate.base_temperature', 0.5);
        $latTempAmp    = (float) config('worldos.planetary_climate.latitude_temperature_amplitude', 0.25);
        $seasonTempAmp = (float) config('worldos.planetary_climate.seasonal_temperature_amplitude', 0.1);
        $seasonalCycle = (int) config('worldos.planetary_climate.seasonal_cycle_ticks', 1000);
        $equatorRain   = (float) config('worldos.planetary_climate.equator_rainfall', 0.75);
        $poleRain      = (float) config('worldos.planetary_climate.pole_rainfall', 0.2);
        $iceThreshold  = (float) config('worldos.planetary_climate.ice_coverage_temp_threshold', 0.25);

        $zoneCount     = max(1, count($zones));
        $updatedZones  = [];
        \Illuminate\Support\Facades\Log::info("ClimateEngine: Processing " . count($zones) . " zones");


        foreach ($zones as $idx => $zone) {
            $zoneId    = (int) ($zone['id'] ?? $idx);
            $zoneState = $zone['state'] ?? [];
            $elevation = (float) ($zoneState['elevation'] ?? 0.5);

            // Tạo latitude giả từ zone index (0 = xích đạo, 1 = cực)
            $latitude = abs(($zoneId / max(1, $zoneCount - 1)) * 2.0 - 1.0);

            // Mùa (seasonal oscillation)
            $seasonalPhase = $seasonalCycle > 0
                ? sin(2.0 * M_PI * $tick / $seasonalCycle)
                : 0.0;

            // Nhiệt độ: giảm theo latitude, giảm theo elevation, dao động theo mùa
            $temperature = $baseTemp
                - $latTempAmp * $latitude
                + $seasonTempAmp * $seasonalPhase
                - $elevation * 0.15; // elevation cooling

            $temperature = max(0.0, min(1.0, $temperature));

            // Lượng mưa: cao ở xích đạo, thấp ở cực
            $rainfall = $equatorRain * (1.0 - $latitude) + $poleRain * $latitude;
            // Núi chắn mưa, cao hơn → ít mưa hơn
            $rainfall *= max(0.3, 1.0 - $elevation * 0.4);
            $rainfall  = max(0.0, min(1.0, $rainfall));

            // Phủ băng
            $iceCoverage = $temperature < $iceThreshold ? 1.0 - ($temperature / $iceThreshold) : 0.0;

            // Biome: dựa vào temperature + rainfall
            $biome = $this->determineBiome($temperature, $rainfall, $iceCoverage);

            $zoneState['temperature']  = round($temperature, 4);
            $zoneState['rainfall']     = round($rainfall, 4);
            $zoneState['ice_coverage'] = round($iceCoverage, 4);
            $zoneState['biome']        = $biome;
            $zone['state'] = $zoneState;
            $updatedZones[] = $zone;
        }

        if (!empty($updatedZones)) {
            $state->set('zones', $updatedZones);
            $result->stateChanges[] = ['zones' => $updatedZones];
        }

        return $result;
    }

    private function determineBiome(float $temp, float $rain, float $ice): string
    {
        if ($ice > 0.5) return 'tundra';
        if ($temp > 0.7 && $rain < 0.3) return 'desert';
        if ($temp > 0.6 && $rain > 0.6) return 'tropical_forest';
        if ($temp > 0.4 && $rain > 0.5) return 'temperate_forest';
        if ($temp > 0.3 && $rain < 0.4) return 'steppe';
        if ($rain > 0.4) return 'grassland';
        return 'barren';
    }
}
