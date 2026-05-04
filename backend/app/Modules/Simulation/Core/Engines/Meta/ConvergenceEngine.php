<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Models\UniverseInteraction;
use Illuminate\Support\Facades\Log;

class ConvergenceEngine
{
    /**
     * Tìm các cặp universe trong cùng world có cộng hưởng cao, đủ điều kiện merge (§5.3).
     * Trả về mảng [ ['a' => idA, 'b' => idB], ... ].
     */
    public function findMergeCandidates(int $worldId): array
    {
        $universeIds = Universe::where('world_id', $worldId)
            ->where('status', 'active')
            ->pluck('id')
            ->toArray();

        if (count($universeIds) < 2) {
            return [];
        }

        $interactions = UniverseInteraction::where('interaction_type', 'resonance')
            ->where('resonance_level', '>=', 0.8)
            ->whereIn('universe_a_id', $universeIds)
            ->whereIn('universe_b_id', $universeIds)
            ->get();

        $seen = [];
        $candidates = [];
        foreach ($interactions as $i) {
            $a = (int) $i->universe_a_id;
            $b = (int) $i->universe_b_id;
            if ($a === $b) {
                continue;
            }
            $key = $a < $b ? "{$a}_{$b}" : "{$b}_{$a}";
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $candidates[] = ['a' => $a, 'b' => $b];
        }

        return $candidates;
    }

    /**
     * Tính toán sự cộng hưởng (Resonance) giữa hai vũ trụ (§5.3).
     */
    public function calculateResonance(Universe $u1, Universe $u2): float
    {
        $snap1 = $u1->latestSnapshot;
        $snap2 = $u2->latestSnapshot;

        if (!$snap1 || !$snap2) return 0.0;

        // 1. State Vector Similarity (Simplified Cosine Similarity on major metrics)
        $sim = $this->compareStateVectors($snap1->state_vector, $snap2->state_vector);

        // 2. Cultural Resonance
        $cultSim = $this->compareCulturalVectors($snap1->state_vector, $snap2->state_vector);

        // Resonance = (State Similarity * 0.4) + (Cultural Similarity * 0.6)
        $resonance = ($sim * 0.4 + $cultSim * 0.6);

        Log::info("Resonance Calculated: Universe [{$u1->id}] <-> [{$u2->id}] = {$resonance}");

        return (float) $resonance;
    }

    protected function compareStateVectors(array $v1, array $v2): float
    {
        $metrics = ['global_entropy', 'knowledge_core', 'sci'];
        $sum = 0.0;
        $count = 0;

        foreach ($metrics as $m) {
            if (isset($v1[$m]) && isset($v2[$m])) {
                $diff = abs($v1[$m] - $v2[$m]);
                $sum += (1.0 - $diff);
                $count++;
            }
        }

        return $count > 0 ? $sum / $count : 0.0;
    }

    protected function compareCulturalVectors(array $v1, array $v2): float
    {
        // Extract averages if available or compare zones
        // For now, let's look at global aggregates if we have them, else pick random zone comparison
        return 0.5; // Placeholder for complex zone-by-zone comparison
    }

    /**
     * Ghi nhận tương tác cộng hưởng vào DB (§5.1).
     */
    public function recordInteraction(Universe $u1, Universe $u2, float $resonance): void
    {
        if ($resonance > 0.8) {
            UniverseInteraction::create([
                'universe_a_id' => $u1->id,
                'universe_b_id' => $u2->id,
                'interaction_type' => 'resonance',
                'resonance_level' => $resonance,
                'synchronicity_score' => $resonance * 0.9,
                'payload' => ['tick' => $u1->current_tick]
            ]);
        }
    }
}

