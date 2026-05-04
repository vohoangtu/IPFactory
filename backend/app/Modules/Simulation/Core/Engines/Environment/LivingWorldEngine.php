<?php
namespace App\Modules\Simulation\Core\Engines\Environment;

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
 * LivingWorldEngine — Hệ sinh thái sống: resource ↔ population.
 *
 * Tạo feedback loop cơ bản:
 * 1. Population tiêu thụ resource
 * 2. Resource tái sinh theo regen_rate
 * 3. Starvation: resource < 0 → population giảm
 * 4. Migration: zone giàu → zone nghèo
 */
class LivingWorldEngine implements EngineInterface
{
    use DefaultSimulationEnginePhase;

    public function name(): string { return 'living_world'; }
    public function phase(): string { return WorldKernel::PHASE_LIFE; }
    public function priority(): int { return 5; }
    public function tickRate(): int { return 1; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $result = new EngineResult();
        $tick   = $ctx->getTick();
        $zones  = $state->getZones();

        if (empty($zones)) {
            return $result;
        }

        $consumptionRate  = (float) config('worldos.living_world.consumption_rate', 5.0);
        $regenBase        = (float) config('worldos.living_world.regen_base', 5.0);
        $deathRate         = (float) config('worldos.living_world.death_rate', 0.1);
        $migrationThreshold = (float) config('worldos.living_world.migration_threshold', 20.0);
        $migrationRate     = (float) config('worldos.living_world.migration_rate', 0.15);

        $updatedZones = [];

        // === Phase 0: Sync Population from Real Actors ===
        $actors = $state->getActorEntities();
        $populationByZone = [];
        foreach ($actors as $actor) {
            if ($actor->isAlive && $actor->zone_id !== null) {
                $populationByZone[$actor->zone_id] = ($populationByZone[$actor->zone_id] ?? 0) + 1;
            }
        }

        // === Phase 1: Consumption & Regeneration ===
        foreach ($zones as $idx => $zone) {
            $zoneState  = $zone['state'] ?? [];
            $zoneId     = $zone['id'] ?? $idx;
            
            // Sync with real actor count if possible, otherwise keep current (for background/unloaded actors)
            $population = (float) ($populationByZone[$zoneId] ?? ($zoneState['population'] ?? 0));
            
            $resource   = (float) ($zoneState['resource'] ?? 50.0);
            $regenRate  = (float) ($zoneState['regen_rate'] ?? $regenBase);
            $capacity   = (float) ($zoneState['resource_capacity'] ?? 100.0);

            // Tiêu thụ (Đã được xử lý chi tiết hơn trong ProcessActorEnergyAction, 
            // nhưng ở đây vẫn giữ logic vĩ mô để ảnh hưởng đến resource chung)
            $consumption = $population * $consumptionRate;
            $resource -= $consumption;

            // Tái sinh (V10: Thêm fertility gradient để tạo ra vùng giàu/nghèo cố định)
            if (!isset($zoneState['fertility'])) {
                // Khởi tạo fertility ngẫu nhiên từ 0.5 đến 1.5 khi chưa có
                $zoneState['fertility'] = 0.5 + (mt_rand(0, 1000) / 1000.0);
            }
            
            $effectiveRegen = $regenRate * $zoneState['fertility'];
            $resource += $effectiveRegen * (0.7 + mt_rand(0, 600) / 1000); // 0.7 to 1.3 variance
            $resource = min($capacity, $resource);

            // Tính toán áp lực tài nguyên (Resource Stress) - Rất quan trọng cho các Rule DSL
            $zoneState['resource_stress'] = $resource > 1.0 ? min(1.0, ($population * $consumptionRate) / $resource) : 1.0;

            // === Phase 2: Macro Starvation (Fallback) ===
            // Nếu resource < 0, gây áp lực lên population (nhưng diễn ra chậm hơn vì cá nhân đã tự xử lý hunger)
            if ($resource < 0) {
                $deaths = min($population, abs($resource) * $deathRate * 0.5);
                $population = max(0, $population - $deaths);
                $resource = 0;

                if ($deaths > 0) {
                    $result->events[] = WorldEvent::create(
                        type: WorldEventType::FAMINE,
                        universeId: $ctx->getUniverseId(),
                        tick: $tick,
                        payload: [
                            'zone_id' => $zoneId,
                            'deaths' => round($deaths, 2),
                            'remaining_population' => round($population, 2),
                            'context' => 'Macro-resource exhaustion'
                        ],
                        impactScore: min(0.5, $deaths / max(1, $population + $deaths))
                    );
                }
            }

            $zoneState['population'] = round($population, 2);
            $zoneState['resource']   = round($resource, 2);
            $zone['state'] = $zoneState;
            $updatedZones[$idx] = $zone;
        }

        // === Phase 3: Migration ===
        // Tìm zone giàu nhất và zone nghèo nhất
        $richest = null;
        $poorest = null;
        $maxSurplus = -INF;
        $minResource = INF;

        foreach ($updatedZones as $idx => $zone) {
            $zoneState  = $zone['state'] ?? [];
            $resource   = (float) ($zoneState['resource'] ?? 0);
            $population = (float) ($zoneState['population'] ?? 0);
            $surplus    = $resource - $population * $consumptionRate;

            if ($surplus > $maxSurplus && $population > 5) {
                $maxSurplus = $surplus;
                $richest = $idx;
            }
            if ($resource < $minResource) {
                $minResource = $resource;
                $poorest = $idx;
            }
        }

        // Di cư nếu có gradient đáng kể
        if ($richest !== null && $poorest !== null && $richest !== $poorest && $maxSurplus > $migrationThreshold) {
            $richState  = $updatedZones[$richest]['state'];
            $poorState  = $updatedZones[$poorest]['state'];
            $migrants   = max(1, round($richState['population'] * $migrationRate));

            $richState['population'] = max(0, $richState['population'] - $migrants);
            $poorState['population'] = $poorState['population'] + $migrants;

            $updatedZones[$richest]['state'] = $richState;
            $updatedZones[$poorest]['state'] = $poorState;

            $result->events[] = WorldEvent::create(
                type: WorldEventType::MIGRATION_WAVE,
                universeId: $ctx->getUniverseId(),
                tick: $tick,
                payload: [
                    'from_zone' => $updatedZones[$richest]['id'] ?? $richest,
                    'to_zone' => $updatedZones[$poorest]['id'] ?? $poorest,
                    'migrants' => $migrants,
                ],
                impactScore: 0.3
            );
        }

        if (!empty($updatedZones)) {
            $state->set('zones', array_values($updatedZones));
            
            // Calculate Global Resource Stress
            $totalStress = 0;
            foreach ($updatedZones as $z) {
                $totalStress += (float) ($z['state']['resource_stress'] ?? 0);
            }
            $globalStress = count($updatedZones) > 0 ? $totalStress / count($updatedZones) : 0;
            $state->set('resource_stress', round($globalStress, 3));

            $result->stateChanges[] = [
                'zones' => array_values($updatedZones),
                'resource_stress' => round($globalStress, 3)
            ];
        }
        return $result;
    }
}
