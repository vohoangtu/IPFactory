<?php

namespace App\Modules\Institutions\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Models\MaterialInstance;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Models\BranchEvent;
use App\Modules\Institutions\Models\InstitutionalEntity;
use Illuminate\Support\Facades\DB;
use App\Modules\Simulation\Core\Support\SimulationRandom;

class ZoneConflictEngine
{
    /**
     * Scan zones within a universe and resolve conflicts between nodes.
     * When $rng is provided, all randomness is deterministic (replayable).
     */
    public function resolveConflicts(Universe $universe, UniverseSnapshot $snapshot, ?SimulationRandom $rng = null): void
    {
        $stateVector = is_string($snapshot->state_vector) ? json_decode($snapshot->state_vector, true) : ($snapshot->state_vector ?? []);
        $zones = $stateVector['zones'] ?? [];

        // Simple check to ensure we have structured zones
        if (empty($zones)) {
            // Find zones from top-level indexing if they weren't wrapped
            $extractedZones = [];
            foreach ($stateVector as $k => $z) {
                if (is_numeric($k) && is_array($z) && isset($z['id'])) {
                    $extractedZones[] = $z;
                }
            }
            if (empty($extractedZones) && isset($stateVector[0]['id'])) {
                $extractedZones = $stateVector;
            }
            $zones = $extractedZones;
        }

        if (count($zones) < 2) {
            return;
        }

        $diplomacy = $stateVector['diplomacy'] ?? [];
        $civMap = $this->buildZoneCivMap($universe->id);

        $conflictsOccurred = false;

        $numZones = count($zones);
        for ($i = 0; $i < $numZones; $i++) {
            $zoneA = $zones[$i];
            $neighborIndex = ($i + 1) % $numZones;
            $zoneB = $zones[$neighborIndex];

            // 1. Identify which civilizations these zones belong to
            $civAId = $civMap[$zoneA['id']] ?? null;
            $civBId = $civMap[$zoneB['id']] ?? null;

            // 2. Check if they are the same civilization (No civil war for now)
            if ($civAId && $civBId && $civAId === $civBId) continue;

            // 3. Check Diplomatic Status
            $isWar = false;
            if ($civAId && $civBId) {
                $relKey = $this->getRelationKey((int)$civAId, (int)$civBId);
                $isWar = ($diplomacy[$relKey]['status'] ?? 'NEUTRAL') === 'WAR';
            } else {
                // If one zone is wild (no civ), conquest is always possible
                $isWar = true;
            }

            $orderA = $this->getMetric($zoneA, 'order');
            $entropyB = $this->getMetric($zoneB, 'entropy');
            $orderB = $this->getMetric($zoneB, 'order');

            // 4. Combat Logic
            if ($orderA > 0.7 && $entropyB > 0.6 && ($orderA - $orderB) > 0.3) {
                if ($isWar) {
                    $this->executeConquest($universe, (int)$snapshot->tick, $zoneA['id'], $zoneB['id']);
                    
                    $zones[$i]['state']['entropy'] = max(0, $zones[$i]['state']['entropy'] - 0.1);
                    $zones[$neighborIndex]['state']['entropy'] = min(1.0, $zones[$neighborIndex]['state']['entropy'] + 0.2);
                    $zones[$neighborIndex]['state']['order'] = max(0, $zones[$neighborIndex]['state']['order'] - 0.3);
                    
                    $zones[$i]['conflict_status'] = 'active';
                    $zones[$neighborIndex]['conflict_status'] = 'active';
                    $conflictsOccurred = true;
                } else {
                    // Diplomatic Crisis instead of War
                    $roll = $rng ? $rng->int(1, 100) : rand(1, 100);
                    if ($roll <= 20) {
                        $this->triggerDiplomaticCrisis($universe, (int)$snapshot->tick, $civAId, $civBId, $zoneA['id'], $zoneB['id']);
                    }
                }
            }
        }

        if ($conflictsOccurred) {
            $stateVector['zones'] = $zones;
            if ($snapshot->exists) {
                $snapshot->state_vector = $stateVector;
                $snapshot->save();
            } else {
                $uv = $universe->state_vector ?? [];
                $uv['zones'] = $zones;
                if (isset($stateVector['diplomacy'])) {
                    $uv['diplomacy'] = $stateVector['diplomacy'];
                }
                $universe->state_vector = $uv;
                $universe->save();
            }
        }
    }

    protected function buildZoneCivMap(int $universeId): array
    {
        $civs = InstitutionalEntity::where('universe_id', $universeId)
            ->where('entity_type', 'CIVILIZATION')
            ->whereNull('collapsed_at_tick')
            ->get();

        $map = [];
        foreach ($civs as $civ) {
            foreach ($civ->influence_map ?? [] as $zoneId) {
                $map[$zoneId] = $civ->id;
            }
        }
        return $map;
    }

    protected function getRelationKey(int $id1, int $id2): string
    {
        $ids = [$id1, $id2];
        sort($ids);
        return "rel_{$ids[0]}_{$ids[1]}";
    }

    protected function triggerDiplomaticCrisis(Universe $universe, int $tick, ?int $civA, ?int $civB, string $zA, string $zB): void
    {
        if (!$civA || !$civB) return;

        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'diplomacy',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => "KHỦNG HOẢNG BIÊN GIỚI: Quân đội vùng [$zA] áp sát [$zB]. Căng thẳng ngoại giao giữa văn minh #$civA và #$civB đang bùng nổ."
            ],
        ]);
    }

    private function getMetric(array $zone, string $key): float
    {
        if (isset($zone['state']) && is_array($zone['state'])) {
            return (float) ($zone['state'][$key] ?? 0);
        }
        return 0;
    }

    private function executeConquest(Universe $universe, int $tick, string $conquerorId, string $victimId): void
    {
        // 1. Lore event
        $flavor = "Trục bánh tuế nguyệt quay cuồng. Thế lực từ phân khu [$conquerorId] đã xua quân san bằng phân khu yếu nhược [$victimId]. Tài nguyên bị tước đoạt, sinh linh lầm than trong biển lửa.";
        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'to_tick' => $tick,
            'type' => 'zone_conflict',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => $flavor
            ],
        ]);

        BranchEvent::create([
            'universe_id' => $universe->id,
            'from_tick' => $tick,
            'event_type' => 'zone_conflict',
            'payload' => [
                'winner_zone' => $conquerorId,
                'loser_zone' => $victimId,
                'description' => $flavor,
            ],
        ]);

        // 2. Transfer Materials
        $materials = MaterialInstance::where('universe_id', $universe->id)->where('lifecycle', 'active')->inRandomOrder()->take(2)->get();
        foreach ($materials as $mat) {
            $ctx = is_array($mat->context) ? $mat->context : [];
            $ctx['plundered_by_zone'] = $conquerorId;
            $ctx['plundered_from_zone'] = $victimId;
            $ctx['plundered_at_tick'] = $tick;
            $mat->update(['context' => $ctx]);
        }
    }
}


