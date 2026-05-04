<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Intelligence\Entities\Contracts\ActorArchetypeInterface;

/**
 * Civilization Attractor Field Engine.
 *
 * Tính toán lực hút (score) của các archetype dựa trên dot product
 * giữa civilization state vector và attractor vector.
 *
 * score = Σ(civilizationState[k] * attractorVector[k])
 */
class CivilizationAttractorEngine
{
    /**
     * Canonical civilization state dimensions.
     * Giới hạn 12 chiều để tránh state vector explosion.
     */
    public const CANONICAL_DIMENSIONS = [
        'knowledge',
        'technology',
        'institution',
        'stability',
        'economy',
        'militarism',
        'population',
        'inequality',
        'culture',
        'spirituality',
        'environment',
        'ai_dependency',
        // Derived dimensions (tính từ canonical)
        'chaos',
        'tradition',
        'trauma',
    ];

    /**
     * Dot product giữa civilization state và archetype attractor vector.
     *
     * @param array<string, float> $civilizationState
     * @param array<string, float> $attractorVector
     */
    public function score(array $civilizationState, array $attractorVector): float
    {
        $score = 0.0;
        foreach ($attractorVector as $dimension => $weight) {
            $score += ($civilizationState[$dimension] ?? 0.0) * $weight;
        }
        return $score;
    }

    /**
     * Đánh giá tất cả archetype eligible, xếp hạng theo score (cao → thấp).
     *
     * @param array<string, float> $civilizationState
     * @param ActorArchetypeInterface[] $archetypes
     * @return array{archetype: ActorArchetypeInterface, score: float}[]
     */
    public function evaluate(array $civilizationState, array $archetypes): array
    {
        $results = [];

        foreach ($archetypes as $archetype) {
            $vector = $archetype->getAttractorVector();
            $results[] = [
                'archetype' => $archetype,
                'score'     => $this->score($civilizationState, $vector),
            ];
        }

        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        return $results;
    }

    /**
     * Trích xuất canonical civilization state vector từ UniverseSnapshot.
     * Chuyển đổi metrics/state_vector thành vector 12-15 chiều chuẩn.
     *
     * @return array<string, float>
     */
    public function extractCivilizationState(UniverseSnapshot $snapshot): array
    {
        $metrics = is_array($snapshot->metrics) ? $snapshot->metrics : [];
        $stateVector = is_array($snapshot->state_vector) ? $snapshot->state_vector : [];

        $stability = $snapshot->stability_index ?? 0.5;
        $entropy = $snapshot->entropy ?? 0.5;

        return [
            'knowledge'     => (float) ($metrics['knowledge'] ?? $stateVector['knowledge'] ?? 0.5),
            'technology'    => (float) ($metrics['technology'] ?? $stateVector['technology'] ?? 0.5),
            'institution'   => (float) ($metrics['institution'] ?? $stateVector['institution'] ?? 0.5),
            'stability'     => (float) $stability,
            'economy'       => (float) ($metrics['economy'] ?? $stateVector['economy'] ?? 0.5),
            'militarism'    => (float) ($metrics['militarism'] ?? $stateVector['militarism'] ?? 0.3),
            'population'    => (float) ($metrics['population'] ?? $stateVector['population'] ?? 0.5),
            'inequality'    => (float) ($metrics['inequality'] ?? $stateVector['inequality'] ?? 0.3),
            'culture'       => (float) ($metrics['culture'] ?? $stateVector['culture'] ?? 0.5),
            'spirituality'  => (float) ($metrics['spirituality'] ?? $stateVector['spirituality'] ?? 0.3),
            'environment'   => (float) ($metrics['environment'] ?? $stateVector['environment'] ?? 0.7),
            'ai_dependency' => (float) ($metrics['ai_dependency'] ?? $stateVector['ai_dependency'] ?? 0.0),
            // Derived
            'chaos'         => (float) $entropy,
            'tradition'     => (float) (1.0 - ($metrics['technology'] ?? $stateVector['technology'] ?? 0.5)),
            'trauma'        => (float) ($metrics['trauma'] ?? $stateVector['trauma'] ?? 0.0),
        ];
    }
}

