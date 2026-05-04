<?php

namespace App\Modules\Simulation\Services\Cosmology;

use App\Contracts\Repositories\UniverseRepositoryInterface;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Institutions\Contracts\SupremeEntityRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Cosmic Energy Pool: universe-level pool fed by cosmic_phase + energy_level and active Supreme Entities.
 * Stored in state_vector.cosmic_energy_pool. Phase 1: cap + decay; optional Phase 2: feed to zones.
 */
class CosmicEnergyPoolService
{
    public function __construct(
        protected UniverseRepositoryInterface $universeRepository,
        protected SupremeEntityRepositoryInterface $supremeEntityRepository
    ) {}

    public function processPulse(Universe $universe, UniverseSnapshot $snapshot): void
    {
        if (! config('worldos.power_economy.enabled', false)) {
            return;
        }

        $metrics = $snapshot->metrics ?? [];
        $cosmicPhase = $metrics['cosmic_phase'] ?? [];
        $phaseStrength = max(0.0, min(1.0, (float) ($cosmicPhase['phase_strength'] ?? 0.5)));
        $energyLevel = max(0.0, min(1.0, (float) ($metrics['energy_level'] ?? 0.5)));

        $inflowScale = (float) config('worldos.power_economy.inflow_scale', 0.1);
        $inflow = $inflowScale * $phaseStrength * $energyLevel;

        $entities = $this->supremeEntityRepository->findByUniverse($universe->id);
        $activeEntities = array_filter($entities, fn($e) => $e->status === 'active');
        foreach ($activeEntities as $entity) {
            $contrib = 0.02 * log(1.0 + $entity->powerLevel);
            $contrib = max(0.0, min(0.15, $contrib));
            $inflow += $contrib;
        }

        $stateVector = is_array($universe->state_vector) ? $universe->state_vector : [];
        $poolData = $stateVector['cosmic_energy_pool'] ?? [];
        $currentPool = max(0.0, (float) ($poolData['pool'] ?? 0));
        $updatedTick = (int) ($poolData['updated_tick'] ?? 0);
        $tick = (int) $snapshot->tick;
        $ticksDelta = max(1, $tick - $updatedTick);

        $decayPerTick = (float) config('worldos.power_economy.decay_per_tick', 0.001);
        $decay = $decayPerTick * $ticksDelta;
        $poolMax = (float) config('worldos.power_economy.cosmic_pool_max', 100.0);

        $pool = $currentPool + $inflow * $ticksDelta - $decay * $currentPool;
        $pool = max(0.0, min($poolMax, $pool));

        $stateVector['cosmic_energy_pool'] = [
            'pool' => round($pool, 4),
            'updated_tick' => $tick,
            'sources' => [
                'inflow_cosmic' => round($inflowScale * $phaseStrength * $energyLevel, 4),
                'inflow_entities' => round($inflow - $inflowScale * $phaseStrength * $energyLevel, 4),
                'decay_per_tick' => $decayPerTick,
            ],
        ];

        if (config('worldos.power_economy.feed_zones', false)) {
            $this->feedZones($universe, $stateVector, $tick);
        } else {
            $this->universeRepository->update($universe->id, ['state_vector' => $stateVector]);
        }
    }

    /**
     * Phase 2: transfer a fraction of pool to zones' free_energy (by resource_capacity or equal).
     */
    protected function feedZones(Universe $universe, array &$stateVector, int $tick): void
    {
        $poolData = $stateVector['cosmic_energy_pool'] ?? [];
        $pool = (float) ($poolData['pool'] ?? 0);
        $feedRatio = (float) config('worldos.power_economy.feed_zones_ratio', 0.01);
        $capPerZone = (float) config('worldos.power_economy.feed_zones_cap_per_zone', 2.0);

        $zones = &$stateVector['zones'];
        if (! is_array($zones) || empty($zones)) {
            $this->universeRepository->update($universe->id, ['state_vector' => $stateVector]);
            return;
        }

        $totalCap = $capPerZone * count($zones);
        $toDistribute = min($pool * $feedRatio, $totalCap);
        if ($toDistribute <= 0) {
            $this->universeRepository->update($universe->id, ['state_vector' => $stateVector]);
            return;
        }

        $perZone = $toDistribute / count($zones);
        foreach ($zones as $i => &$zone) {
            if (! isset($zone['state'])) {
                $zone['state'] = [];
            }
            $current = (float) ($zone['state']['free_energy'] ?? 0);
            $zone['state']['free_energy'] = round($current + $perZone, 4);
        }
        unset($zone);

        $poolData['pool'] = max(0, round($pool - $toDistribute, 4));
        $stateVector['cosmic_energy_pool'] = $poolData;
        $this->universeRepository->update($universe->id, ['state_vector' => $stateVector]);
    }
}

