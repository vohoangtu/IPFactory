<?php

namespace App\Modules\Simulation\Services\Core;

use App\Modules\World\Models\Universe;
use App\Modules\Institutions\Models\InstitutionalEntity;

/**
 * Deep Sim Phase B: spawn macro agents (ruler, army) when conditions are met;
 * merge into state_vector.macro_agents and persist.
 *
 * Spawn conditions:
 * - Ruler: zone has a CIVILIZATION institution (influence_map) and no ruler for that zone.
 * - Army: zone has high war_pressure or is in conflict; limit per zone.
 *
 * Limits (B.3): max agents per zone, max total; no duplicate ruler per zone.
 */
class MacroAgentSpawnService
{
    public const DEFAULT_MAX_AGENTS_PER_ZONE = 3;

    public const DEFAULT_MAX_AGENTS_TOTAL = 20;

    public const WAR_PRESSURE_THRESHOLD = 0.5;

    public function __construct(
        protected \App\Contracts\Repositories\UniverseRepositoryInterface $universeRepository
    ) {}

    /**
     * Evaluate spawn conditions and append new macro agents to universe state_vector; persist.
     */
    public function spawnIfEligible(Universe $universe, object $snapshot): void
    {
        $vec = is_array($universe->state_vector) ? $universe->state_vector : [];
        $zones = $vec['zones'] ?? [];
        $agents = is_array($vec['macro_agents'] ?? null) ? $vec['macro_agents'] : [];

        $maxPerZone = (int) config('worldos.macro_agents.max_per_zone', self::DEFAULT_MAX_AGENTS_PER_ZONE);
        $maxTotal = (int) config('worldos.macro_agents.max_total', self::DEFAULT_MAX_AGENTS_TOTAL);

        $rulerZones = $this->zonesWithCivilization($universe->id);
        $zoneAgentCount = $this->countAgentsPerZone($agents);

        $rng = $universe->seed ? ((int) $universe->seed + (int) ($snapshot->tick ?? 0)) % 10000 : 0;

        // Spawn ruler: one per zone that has a civ and no ruler yet
        foreach ($rulerZones as $zoneId) {
            if ($this->hasRulerForZone($agents, (int) $zoneId)) {
                continue;
            }
            if (($zoneAgentCount[$zoneId] ?? 0) >= $maxPerZone || count($agents) >= $maxTotal) {
                continue;
            }
            $strength = 0.5 + 0.3 * (($rng + $zoneId) % 10) / 10.0; // 0.5–0.8
            $agents[] = [
                'zone_id' => (int) $zoneId,
                'type' => 'ruler',
                'strength' => round($strength, 3),
            ];
            $zoneAgentCount[$zoneId] = ($zoneAgentCount[$zoneId] ?? 0) + 1;
        }

        // Spawn army: zones with high war_pressure, no duplicate per zone beyond limit
        $warThreshold = (float) config('worldos.macro_agents.war_pressure_threshold', self::WAR_PRESSURE_THRESHOLD);
        foreach ($zones as $idx => $zone) {
            $zoneId = (int) ($zone['id'] ?? $idx);
            $state = $zone['state'] ?? [];
            $warPressure = (float) ($state['war_pressure'] ?? 0);
            if ($warPressure < $warThreshold) {
                continue;
            }
            $count = $zoneAgentCount[$zoneId] ?? 0;
            if ($count >= $maxPerZone || count($agents) >= $maxTotal) {
                continue;
            }
            $armyCount = $this->countAgentsByTypeInZone($agents, $zoneId, 'army');
            if ($armyCount >= 1) {
                continue; // at most one army per zone from spawn
            }
            $strength = 0.3 + 0.3 * (($rng + $zoneId + 1) % 10) / 10.0; // 0.3–0.6
            $agents[] = [
                'zone_id' => $zoneId,
                'type' => 'army',
                'strength' => round($strength, 3),
            ];
            $zoneAgentCount[$zoneId] = ($zoneAgentCount[$zoneId] ?? 0) + 1;
        }

        if ($agents === (is_array($vec['macro_agents'] ?? null) ? $vec['macro_agents'] : [])) {
            return;
        }

        $vec['macro_agents'] = $agents;
        $this->universeRepository->update($universe->id, ['state_vector' => $vec]);
    }

    /**
     * Zone IDs that have a CIVILIZATION institution (from influence_map keys or primary zone).
     *
     * @return array<int, int>
     */
    private function zonesWithCivilization(int $universeId): array
    {
        $institutions = InstitutionalEntity::where('universe_id', $universeId)
            ->whereNull('collapsed_at_tick')
            ->where('entity_type', 'CIVILIZATION')
            ->get();

        $zoneIds = [];
        foreach ($institutions as $inst) {
            $map = $inst->influence_map;
            if (! is_array($map)) {
                continue;
            }
            foreach (array_keys($map) as $key) {
                $zoneId = is_numeric($key) ? (int) $key : null;
                if ($zoneId !== null && $zoneId >= 0) {
                    $zoneIds[$zoneId] = true;
                }
            }
            // If influence_map is empty but we have a civ, we might have zone in another shape; skip.
        }
        return array_keys($zoneIds);
    }

    /** @param array<int, array{zone_id?: int, type?: string}> $agents */
    private function hasRulerForZone(array $agents, int $zoneId): bool
    {
        foreach ($agents as $a) {
            if ((int) ($a['zone_id'] ?? 0) === $zoneId && ($a['type'] ?? '') === 'ruler') {
                return true;
            }
        }
        return false;
    }

    /** @param array<int, array{zone_id?: int, type?: string}> $agents */
    private function countAgentsByTypeInZone(array $agents, int $zoneId, string $type): int
    {
        $n = 0;
        foreach ($agents as $a) {
            if ((int) ($a['zone_id'] ?? 0) === $zoneId && ($a['type'] ?? '') === $type) {
                $n++;
            }
        }
        return $n;
    }

    /** @return array<int, int> zone_id => count */
    private function countAgentsPerZone(array $agents): array
    {
        $count = [];
        foreach ($agents as $a) {
            $z = (int) ($a['zone_id'] ?? 0);
            $count[$z] = ($count[$z] ?? 0) + 1;
        }
        return $count;
    }
}


