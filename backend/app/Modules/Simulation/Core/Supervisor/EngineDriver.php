<?php

namespace App\Modules\Simulation\Core\Supervisor;

use App\Contracts\SimulationEngineClientInterface;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Services\Ecology\GeographyResourceService;
use App\Modules\Simulation\Core\Contracts\StateCacheInterface;

/**
 * Drives the simulation engine: prepare state/config, call advance, ensure entropy/zones.
 * When state_cache=redis, prefers cached state for prepareEngineStateInput if cache tick >= universe.current_tick (Phase 2 §2.3).
 */
final class EngineDriver
{    public function __construct(
        private readonly SimulationEngineClientInterface $engine,
        private readonly GeographyResourceService $geographyResource,
        private readonly StateCacheInterface $stateCache,
        private readonly \App\Modules\Simulation\Contracts\WorldRepositoryInterface $worldRepository,
    ) {}

    /**
     * @return array{ok: bool, snapshot?: array, error_message?: string, _tick_duration_ms_per_tick?: float}
     */
    public function advance(\App\Modules\Simulation\Entities\UniverseEntity $universe, int $ticks): array
    {
        $stateInput = $this->prepareEngineStateInput($universe);
        $worldConfig = $this->prepareWorldConfig($universe);

        $tickStart = microtime(true);
        $response = $this->engine->advance((int) $universe->id, $ticks, $stateInput, $worldConfig);
        $tickDurationMs = (microtime(true) - $tickStart) * 1000;
        $tickDurationMsPerTick = $ticks > 0 ? $tickDurationMs / $ticks : $tickDurationMs;
        $response['_tick_duration_ms_per_tick'] = $tickDurationMsPerTick;

        if (! ($response['ok'] ?? false)) {
            return $response;
        }

        $snapshotData = &$response['snapshot'];
        if (! empty($snapshotData)) {
            $this->ensureEntropyFloor($snapshotData);
            $this->ensureStateVectorHasZones($snapshotData);

            // Phase 2: Preserve custom fields added by Laravel engines that Rust doesn't know about
            if (isset($snapshotData['zones']) && isset($stateInput['zones'])) {
                foreach ($snapshotData['zones'] as &$newZone) {
                    $zoneId = $newZone['id'] ?? null;
                    if ($zoneId !== null) {
                        $oldZone = collect($stateInput['zones'])->first(fn($z) => ($z['id'] ?? null) == $zoneId);
                        if ($oldZone && isset($oldZone['state'])) {
                            $newZone['state'] = array_merge($oldZone['state'], $newZone['state']);
                        }
                    }
                }
            }
        }

        return $response;
    }

    private function prepareEngineStateInput(\App\Modules\Simulation\Entities\UniverseEntity $universe): array
    {
        $currentTick = (int) $universe->currentTick;
        $cached = $this->stateCache->get((int) $universe->id);
        if ($cached !== null && ($cached['tick'] ?? 0) >= $currentTick && isset($cached['state_vector']) && is_array($cached['state_vector'])) {
            $vec = $cached['state_vector'];
        } else {
            $vec = is_array($universe->stateVector) ? $universe->stateVector : [];
        }
        $zones = [];
        $globalEntropy = $vec['entropy'] ?? 0.0;
        $knowledgeCore = $vec['knowledge_core'] ?? 0.0;
        $scars = $vec['scars'] ?? [];

        if (isset($vec['zones'])) {
            $zones = $vec['zones'];
            $globalEntropy = $vec['global_entropy'] ?? $globalEntropy;
            $resourceCapacityMap = $this->geographyResource->getResourceCapacityForZones($zones, (int) $universe->id);
            foreach ($zones as $idx => &$zone) {
                if (! isset($zone['state']['structured_mass'])) {
                    $zone['state']['structured_mass'] = 50.0;
                }
                $zoneId = (int) ($zone['id'] ?? $idx);
                $zone['state']['resource_capacity'] = $resourceCapacityMap[$zoneId] ?? 0.5;
            }
        }

        $institutions = \App\Modules\Institutions\Models\InstitutionalEntity::where('universe_id', $universe->id)
            ->whereNull('collapsed_at_tick')
            ->get();

        return [
            'universe_id' => $universe->id,
            'tick' => (int) $universe->currentTick,
            'zones' => array_values($zones),
            'global_entropy' => (float) $globalEntropy,
            'knowledge_core' => (float) $knowledgeCore,
            'scars' => array_values(array_filter($scars, fn ($s) => is_array($s) && isset($s['tick']) && isset($s['category']))),
            'attractors' => array_values(is_array($vec['attractors'] ?? null) ? $vec['attractors'] : []),
            'dark_attractors' => array_values(is_array($vec['dark_attractors'] ?? null) ? $vec['dark_attractors'] : []),
            'institutions' => array_values($institutions->map(fn ($e) => [
                'id' => $e->id,
                'type' => $e->entity_type,
                'capacity' => $e->org_capacity,
                'ideology' => $e->ideology_vector,
                'legitimacy' => $e->legitimacy,
                'influence' => $e->influence_map,
            ])->toArray()),
            'macro_agents' => array_values(is_array($vec['macro_agents'] ?? null) ? $vec['macro_agents'] : []),
        ];
    }

    private function prepareWorldConfig(\App\Modules\Simulation\Entities\UniverseEntity $universe): array
    {
        $world = $this->worldRepository->findById($universe->worldId);
        if (!$world) {
             throw new \RuntimeException("World not found for universe {$universe->id}");
        }

        return [
            'world_id' => $world->id,
            'origin' => $world->origin ?? 'generic',
            'axiom' => $world->axiom,
            'world_seed' => $world->worldSeed,
            'genome' => empty($universe->kernelGenome) ? null : $universe->kernelGenome,
        ];
    }

    private function ensureEntropyFloor(array &$snapshotData): void
    {
        $tick = (int) ($snapshotData['tick'] ?? 0);
        if ($tick <= 0) {
            return;
        }
        $floor = (float) config('worldos.entropy_floor', 0.001);
        $entropy = $snapshotData['entropy'] ?? 0;
        if ($entropy === null || $entropy === 0 || (is_float($entropy) && $entropy < $floor)) {
            $snapshotData['entropy'] = $floor;
        }
    }

    private function ensureStateVectorHasZones(array &$snapshotData): void
    {
        $raw = $snapshotData['state_vector'] ?? null;
        $stateVector = is_string($raw) ? (json_decode($raw, true) ?? []) : (is_array($raw) ? $raw : []);

        if (isset($stateVector['zones']) && is_array($stateVector['zones']) && count($stateVector['zones']) > 0) {
            return;
        }
        if (isset($stateVector[0]['state'])) {
            $snapshotData['state_vector'] = ['zones' => $stateVector];
            return;
        }
        $tick = (int) ($snapshotData['tick'] ?? 0);
        $entropy = (float) ($snapshotData['entropy'] ?? 0.3);
        $order = 1.0 - $entropy * 0.5;
        $stateVector['zones'] = [
            [
                'id' => 0,
                'state' => [
                    'entropy' => $entropy > 0.0 ? $entropy : 0.5,
                    'order' => max(0, min(1, $order)),
                    'base_mass' => 100.0,
                    'structured_mass' => 50.0,
                    'active_materials' => [],
                    'civ_fields' => [],
                    'cultural' => [],
                    'resource_capacity' => 0.5,
                    'wealth_proxy' => 0.0,
                ],
                'neighbors' => [],
            ],
        ];
        $snapshotData['state_vector'] = $stateVector;
    }
}


