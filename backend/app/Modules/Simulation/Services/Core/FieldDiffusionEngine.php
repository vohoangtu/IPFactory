<?php

namespace App\Modules\Simulation\Services\Core;

/**
 * FieldDiffusionEngine – propagates 5 Attractor Fields between zones each tick.
 *
 * Without diffusion every zone is isolated; civilization cannot spread.
 * This engine makes fields flow like heat/fluid between neighboring zones:
 *
 *   ΔF_i = k × Σ(F_neighbor − F_i)   for each field
 *   k = diffusion_rate × terrain_modifier
 *
 * Institution effects (field sources per tick):
 *   academy / library   → knowledge +0.2
 *   military / fortress → power +0.3, survival +0.1
 *   market / guild      → wealth +0.25
 *   temple / church     → meaning +0.3
 *
 * Emergent phenomena from diffusion:
 *   - trade corridor   : wealth diffuses along river paths
 *   - empire border    : power gradient between capital and periphery
 *   - cultural sphere  : knowledge diffuses from academy hub
 *   - religious spread : meaning field spreads aggressively
 */
class FieldDiffusionEngine
{
    /** How fast fields spread between neighboring zones per tick */
    const DEFAULT_K = 0.05;

    /** Natural decay per tick (fields fade without sources) */
    const DECAY_RATE = 0.005;

    /** All 5 civilization fields */
    const FIELDS = ['survival', 'power', 'wealth', 'knowledge', 'meaning'];

    /** Terrain diffusion modifiers */
    const TERRAIN_MODIFIERS = [
        'mountain' => 0.2,
        'ocean'    => 0.15,
        'river'    => 1.3,
        'plain'    => 1.0,
        'forest'   => 0.7,
        'desert'   => 0.5,
    ];

    /** Institution → field boost map (per tick, applied before diffusion) */
    const INSTITUTION_BOOSTS = [
        'academy'    => ['knowledge' => 0.20],
        'library'    => ['knowledge' => 0.15],
        'university' => ['knowledge' => 0.25],
        'military'   => ['power' => 0.30, 'survival' => 0.10],
        'fortress'   => ['power' => 0.25, 'survival' => 0.15],
        'market'     => ['wealth' => 0.25, 'power' => 0.05],
        'guild'      => ['wealth' => 0.20],
        'temple'     => ['meaning' => 0.30],
        'church'     => ['meaning' => 0.25],
        'shrine'     => ['meaning' => 0.15],
        'regime'     => ['power' => 0.20, 'stability' => 0.10],
        'corporation' => ['wealth' => 0.30, 'knowledge' => 0.10],
        'religion'   => ['meaning' => 0.35, 'power' => 0.10],
    ];

    /**
     * Run a full diffusion tick on all zones.
     *
     * @param array $zones        Keyed by zone_id, each has 'fields' => [5 values] and optional 'neighbors' => [zone_ids]
     * @param array $institutions List of active institution records with keys: zone_id, entity_type
     * @return array              Updated zones array with new field values
     */
    public function diffuse(array $zones, array $institutions = []): array
    {
        // Step 1: Apply institution field boosts (field sources)
        $zones = $this->applyInstitutionBoosts($zones, $institutions);

        // Step 2: Compute new field values via diffusion
        $updated = [];
        foreach ($zones as $zoneId => $zone) {
            $fields    = $this->ensureFields($zone['fields'] ?? []);
            $neighbors = $zone['neighbors'] ?? [];
            $terrain   = $zone['terrain'] ?? 'plain';
            $k         = self::DEFAULT_K * (self::TERRAIN_MODIFIERS[$terrain] ?? 1.0);

            $newFields = $fields;
            foreach (self::FIELDS as $field) {
                $delta = 0.0;
                $count = 0;
                foreach ($neighbors as $neighborId) {
                    if (!isset($zones[$neighborId])) continue;
                    $neighborFields = $this->ensureFields($zones[$neighborId]['fields'] ?? []);
                    $diff = $neighborFields[$field] - $fields[$field];
                    $delta += $diff;
                    $count++;
                }
                if ($count > 0) {
                    $newFields[$field] = $this->clamp($fields[$field] + $k * $delta);
                }

                // Natural decay
                $newFields[$field] = $this->clamp($newFields[$field] - self::DECAY_RATE);
            }

            $updated[$zoneId] = array_merge($zone, ['fields' => $newFields]);
        }

        return $updated;
    }

    /**
     * Apply institution boosts to their zone fields before diffusion.
     * Institutions act as "field sources" that continuously emit their type of field.
     */
    protected function applyInstitutionBoosts(array $zones, array $institutions): array
    {
        foreach ($institutions as $inst) {
            $zoneId = $inst['zone_id'] ?? null;
            $type   = strtolower($inst['entity_type'] ?? '');
            if (!$zoneId || !isset($zones[$zoneId])) continue;

            $boosts = self::INSTITUTION_BOOSTS[$type] ?? [];
            $fields = $this->ensureFields($zones[$zoneId]['fields'] ?? []);
            foreach ($boosts as $field => $boost) {
                if (array_key_exists($field, $fields)) {
                    $fields[$field] = $this->clamp($fields[$field] + $boost * 0.1); // Scaled by 0.1 per tick
                }
            }
            $zones[$zoneId]['fields'] = $fields;
        }
        return $zones;
    }

    /**
     * Compute global field averages from all zones.
     * Used by CivilizationFieldEngine for backward aggregation.
     */
    public function aggregateGlobalFields(array $zones): array
    {
        if (empty($zones)) {
            return array_fill_keys(self::FIELDS, 0.5);
        }

        $totals = array_fill_keys(self::FIELDS, 0.0);
        $count  = 0;
        foreach ($zones as $zone) {
            $fields = $this->ensureFields($zone['fields'] ?? []);
            foreach (self::FIELDS as $f) {
                $totals[$f] += $fields[$f];
            }
            $count++;
        }

        $result = [];
        foreach (self::FIELDS as $f) {
            $result[$f] = $count > 0 ? round($totals[$f] / $count, 4) : 0.5;
        }
        return $result;
    }

    /**
     * Build a simple neighbor map for a grid topology (for testing/seeding).
     * In production, neighbors are pre-computed and stored in zone data.
     *
     * @param  int[] $zoneIds Ordered list of zone IDs in a 1D strip
     * @return array          zone_id => neighbor zone_ids
     */
    public function buildLinearNeighborMap(array $zoneIds): array
    {
        $map = [];
        $n   = count($zoneIds);
        for ($i = 0; $i < $n; $i++) {
            $neighbors = [];
            if ($i > 0)      $neighbors[] = $zoneIds[$i - 1];
            if ($i < $n - 1) $neighbors[] = $zoneIds[$i + 1];
            $map[$zoneIds[$i]] = $neighbors;
        }
        return $map;
    }

    protected function ensureFields(array $fields): array
    {
        $defaults = array_fill_keys(self::FIELDS, 0.3);
        return array_merge($defaults, array_intersect_key($fields, $defaults));
    }

    protected function clamp(float $value): float
    {
        return max(0.0, min(1.0, round($value, 4)));
    }
}

