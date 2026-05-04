<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Models\Actor;
use App\Modules\Institutions\Models\InstitutionalEntity;

/**
 * World-Will Engine: Calculates global ideological alignment (Celestial Intent).
 * Maps 17D Actor traits and Institutional patterns to macro-alignments.
 */
class WorldWillEngine
{
    /**
     * Calculate global alignment vector for a universe.
     * Returns percentages for Spirituality, Hard-Tech, and Entropy.
     */
    public function calculateAlignment(Universe $universe): array
    {
        // 1. Accumulate Actor traits (17D)
        $driver = \DB::getDriverName();
        $query = \DB::table('actors')
            ->where('universe_id', $universe->id)
            ->where('is_alive', true);
        if ($driver === 'pgsql') {
            // Postgres JSONB array indexing and numeric casting
            $actorStats = $query->selectRaw('
                AVG((traits->>4)::float)  as empathy,
                AVG((traits->>5)::float)  as solidarity,
                AVG((traits->>9)::float)  as dogmatism,
                AVG((traits->>7)::float)  as pragmatism,
                AVG((traits->>1)::float)  as ambition,
                AVG((traits->>8)::float)  as curiosity,
                AVG((traits->>11)::float) as fear,
                AVG((traits->>12)::float) as vengeance
            ')->first();
        } else {
            // MySQL/MariaDB
            $actorStats = $query->selectRaw('
                AVG(JSON_EXTRACT(traits, "$[4]"))  as empathy,
                AVG(JSON_EXTRACT(traits, "$[5]"))  as solidarity,
                AVG(JSON_EXTRACT(traits, "$[9]"))  as dogmatism,
                AVG(JSON_EXTRACT(traits, "$[7]"))  as pragmatism,
                AVG(JSON_EXTRACT(traits, "$[1]"))  as ambition,
                AVG(JSON_EXTRACT(traits, "$[8]"))  as curiosity,
                AVG(JSON_EXTRACT(traits, "$[11]")) as fear,
                AVG(JSON_EXTRACT(traits, "$[12]")) as vengeance
            ')->first();
        }

        // Fallback for empty actors
        if (!$actorStats || $actorStats->empathy === null) {
            return [
                'spirituality' => 0.33,
                'hardtech' => 0.33,
                'entropy' => 0.34
            ];
        }

        // 2. Map to Alignments (Simplified V6 Logic)
        // Spirituality: Empathy + Solidarity + Dogmatism
        $spiritScore = ($actorStats->empathy + $actorStats->solidarity + $actorStats->dogmatism) / 3.0;

        // Hard-Tech: Pragmatism + Ambition + Curiosity
        $techScore = ($actorStats->pragmatism + $actorStats->ambition + $actorStats->curiosity) / 3.0;

        // Entropy (Chaos): Fear + Vengeance + Global Entropy (from universe)
        $vec = $universe->state_vector ?? [];
        $globalEntropy = (float)($vec['entropy'] ?? 0.5);
        $chaosScore = ($actorStats->fear + $actorStats->vengeance + $globalEntropy) / 3.0;

        // 3. Institutional Bonus
        $institutions = InstitutionalEntity::where('universe_id', $universe->id)
            ->whereNull('collapsed_at_tick')
            ->get();

        foreach ($institutions as $inst) {
            $type = $inst->entity_type; // religion, corporation, regime
            if ($type === 'religion') $spiritScore += 0.05 * $inst->legitimacy;
            if ($type === 'corporation') $techScore += 0.05 * ($inst->org_capacity / 100.0);
            if ($inst->legitimacy < 0.2) $chaosScore += 0.05;
        }

        // 4. Normalize
        $total = $spiritScore + $techScore + $chaosScore;
        if ($total == 0) return ['spirituality' => 0.33, 'hardtech' => 0.33, 'entropy' => 0.34];

        return [
            'spirituality' => round($spiritScore / $total, 3),
            'hardtech' => round($techScore / $total, 3),
            'entropy' => round($chaosScore / $total, 3)
        ];
    }

    /**
     * Get dominant alignment string.
     */
    public function getDominantAlignment(array $alignment): string
    {
        $max = max($alignment);
        return array_search($max, $alignment);
    }

    /**
     * Map alignment vector to 5 CivilizationField hints.
     * Used by CivilizationFieldEngine as supplementary input.
     *
     * @return array{spirituality_hint: float, hardtech_hint: float, chaos_hint: float}
     */
    public function toFieldVector(Universe $universe): array
    {
        $alignment = $this->calculateAlignment($universe);
        return [
            'spirituality_hint' => $alignment['spirituality'],
            'hardtech_hint'     => $alignment['hardtech'],
            'chaos_hint'        => $alignment['entropy'],
        ];
    }
}

