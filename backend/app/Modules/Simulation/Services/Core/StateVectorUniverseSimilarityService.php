<?php

namespace App\Modules\Simulation\Services\Core;

use App\Contracts\UniverseSimilarityServiceInterface;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;

/**
 * doc §13: merge when similarity between two universes > threshold.
 * Compares state_vector (entropy, fields, zones) with sibling universes in the same world.
 */
final class StateVectorUniverseSimilarityService implements UniverseSimilarityServiceInterface
{
    private const FIELD_KEYS = ['survival', 'power', 'wealth', 'knowledge', 'meaning'];

    /**
     * @return array{universe_id: int, similarity: float, state_vector: mixed}|null
     */
    public function getMergeCandidate(UniverseSnapshot $snapshot): ?array
    {
        $threshold = (float) config('worldos.autonomic.merge_similarity_threshold', 0.92);
        $neighbors = $this->getNeighbors($snapshot, $threshold);
        
        if (empty($neighbors)) return null;

        // Return the best one
        usort($neighbors, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
        return $neighbors[0];
    }

    public function getNeighbors(UniverseSnapshot $snapshot, float $threshold = 0.5): array
    {
        $universe = $snapshot->universe;
        if (!$universe || !$universe->world_id) {
            return [];
        }

        $currentVec = $this->extractFeatureVector($snapshot);
        $siblings = Universe::where('world_id', $universe->world_id)
            ->where('status', 'active')
            ->where('id', '!=', $universe->id)
            ->get();

        $neighbors = [];
        foreach ($siblings as $sibling) {
            $siblingSnap = UniverseSnapshot::where('universe_id', $sibling->id)
                ->orderByDesc('tick')
                ->first();
            if (!$siblingSnap || !$siblingSnap->state_vector) {
                continue;
            }

            $siblingVec = $this->extractFeatureVector($siblingSnap);
            $similarity = $this->similarity($currentVec, $siblingVec);
            
            if ($similarity >= $threshold) {
                $neighbors[] = [
                    'universe_id' => (int) $sibling->id,
                    'similarity'  => round($similarity, 4),
                    'state_vector' => $siblingSnap->state_vector, // Load state vector for bleeding
                ];
            }
        }

        return $neighbors;
    }

    /**
     * Extract a comparable feature vector from snapshot state_vector.
     *
     * @return array<string, float>
     */
    private function extractFeatureVector(UniverseSnapshot $snapshot): array
    {
        $vec = (array) ($snapshot->state_vector ?? []);
        $fields = (array) ($vec['fields'] ?? []);

        $features = [
            'entropy' => (float) ($snapshot->entropy ?? $vec['entropy'] ?? 0.5),
        ];
        foreach (self::FIELD_KEYS as $key) {
            $features[$key] = (float) ($fields[$key] ?? 0.5);
        }
        $zones = $vec['zones'] ?? [];
        $features['zones_count'] = is_array($zones)
            ? min(1.0, (float) count($zones) / 20.0)
            : 0.0;

        return $features;
    }

    /**
     * Similarity in [0, 1]: 1 - normalized Euclidean distance.
     *
     * @param array<string, float> $a
     * @param array<string, float> $b
     */
    private function similarity(array $a, array $b): float
    {
        $keys = array_keys($a);
        $sum = 0.0;
        $n = 0;
        foreach ($keys as $k) {
            if (!array_key_exists($k, $b)) {
                continue;
            }
            $diff = ($a[$k] ?? 0.0) - ($b[$k] ?? 0.0);
            $sum += $diff * $diff;
            $n++;
        }
        if ($n === 0) {
            return 0.0;
        }
        $dist = sqrt($sum / $n);
        return max(0.0, min(1.0, 1.0 - $dist));
    }
}


