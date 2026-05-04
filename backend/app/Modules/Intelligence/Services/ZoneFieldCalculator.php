<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\Intelligence\Domain\Society\SocialField;
use App\Modules\Intelligence\Domain\BehaviorStats;

/**
 * Phase 28: Level 7 Attractor Field Calculator.
 * Computes the 5 field vectors (S, P, W, K, M) for a specific zone based on local actor stats and world state.
 */
class ZoneFieldCalculator
{
    /**
     * Calculate fields for a set of actors in a zone.
     * 
     * @param array $actors List of ActorState or similar in this zone
     * @param float $entropy Global or local entropy
     * @param float $stability Structural coherence
     * @param array $factions Factions present in this zone/universe
     * @return array [S, P, W, K, M]
     */
    public function calculate(
        array $actors,
        float $entropy,
        float $stability,
        array $factions = [],
        array $institutionMods = []
    ): array {
        if (empty($actors)) {
            return [
                'survival' => $stability * (1 - $entropy),
                'power' => 0.0,
                'wealth' => 0.0,
                'knowledge' => 0.0,
                'meaning' => $entropy * 0.2
            ];
        }

        $totalActors = count($actors);
        $sumTraits = [];
        $sumStats = [
            'battles' => 0,
            'research' => 0,
            'trade' => 0,
            'spiritual' => 0
        ];

        foreach ($actors as $actor) {
            foreach ($actor->traits as $trait => $value) {
                $sumTraits[$trait] = ($sumTraits[$trait] ?? 0) + $value;
            }
            
            $stats = BehaviorStats::fromArray($actor->metrics['behavior_stats'] ?? []);
            $sumStats['battles'] += $stats->battlesJoined;
            $sumStats['research'] += $stats->researchActions;
            $sumStats['trade'] += $stats->tradeActions;
            $sumStats['spiritual'] += $stats->spiritualActions;
        }

        $avgTraits = array_map(fn($v) => $v / $totalActors, $sumTraits);

        // Map numeric indices to local variables for clarity
        // Indices derived from ActorEntity::TRAIT_DIMENSIONS
        $traits = [
            'dominance'     => $avgTraits[0]  ?? 0.5,
            'ambition'      => $avgTraits[1]  ?? 0.5,
            'coercion'      => $avgTraits[2]  ?? 0.5,
            'loyalty'       => $avgTraits[3]  ?? 0.5,
            'empathy'       => $avgTraits[4]  ?? 0.5,
            'solidarity'    => $avgTraits[5]  ?? 0.5,
            'conformity'    => $avgTraits[6]  ?? 0.5,
            'pragmatism'    => $avgTraits[7]  ?? 0.5,
            'curiosity'     => $avgTraits[8]  ?? 0.5,
            'dogmatism'     => $avgTraits[9]  ?? 0.5,
            'resilience'    => $avgTraits[10] ?? 0.5, // RiskTolerance/Resilience
            'fear'          => $avgTraits[11] ?? 0.5,
            'hope'          => $avgTraits[13] ?? 0.5,
            'pride'         => $avgTraits[15] ?? 0.5,
            'longevity'     => $avgTraits[17] ?? 0.5,
        ];
        
        // 1. Survival: Khả năng sống sót cơ bản trước entropy
        $s = ($traits['resilience'] * 0.5) + ((1 - $entropy) * 0.3) + ($traits['fear'] * 0.2);

        // 2. Reproduction: Động lực duy trì nòi giống và dòng dõi
        // Note: Vitality is in physic metrics, but longevity trait is a proxy for reproductive drive
        $r = ($traits['longevity'] * 0.4) + ($stability * 0.3) + ($traits['hope'] * 0.3);

        // 3. Wealth: Tích lũy tài nguyên và sản xuất
        $tradeActivity = min(1.0, $sumStats['trade'] / ($totalActors * 5 + 1));
        $w = ($traits['pragmatism'] * 0.4) + ($traits['ambition'] * 0.3) + ($tradeActivity * 0.3);

        // 4. Power: Kiểm soát, quyền lực và bành trướng định chế
        $p = ($traits['dominance'] * 0.4) + ($traits['ambition'] * 0.4) + ($traits['coercion'] * 0.2);

        // 5. Knowledge: Tri thức, tò mò và phát minh
        $researchActivity = min(1.0, $sumStats['research'] / ($totalActors * 5 + 1));
        $k = ($traits['curiosity'] * 0.6) + ($researchActivity * 0.4);

        // 6. Meaning: Ý nghĩa tâm linh, hệ tư tưởng và lý tính
        $spiritualActivity = min(1.0, $sumStats['spiritual'] / ($totalActors * 5 + 1));
        $m = ($traits['hope'] * 0.4) + ($spiritualActivity * 0.3) + ($traits['dogmatism'] * 0.3);

        // 7. Status: Vị thế xã hội, danh dự và lòng kiêu hãnh
        $st = ($traits['pride'] * 0.5) + ($traits['dominance'] * 0.3) + ($traits['ambition'] * 0.2);

        // 8. Belonging: Nhu cầu thuộc về nhóm, đoàn kết và trung thành
        $b = ($traits['solidarity'] * 0.4) + ($traits['conformity'] * 0.3) + ($traits['loyalty'] * 0.3);

        $results = [
            'survival' => $s,
            'reproduction' => $r,
            'wealth' => $w,
            'power' => $p,
            'knowledge' => $k,
            'meaning' => $m,
            'status' => $st,
            'belonging' => $b,
        ];

        // 9. Great Person Aura Effect (Phase 44)
        foreach ($actors as $actor) {
            if ($actor->isHeroic && $actor->heroicType) {
                $fieldKey = $this->mapHeroicTypeToField($actor->heroicType);
                if (isset($results[$fieldKey])) {
                    $results[$fieldKey] += 0.2; // Aura intensity
                }
            }
        }

        // Apply Institutional Modifiers
        foreach ($results as $key => $val) {
            if (isset($institutionMods[$key])) {
                $results[$key] *= $institutionMods[$key];
            }
        }

        // Final Rounding & Clamping
        foreach ($results as $key => $val) {
            $results[$key] = round(max(0.0, min(1.0, $results[$key])), 4);
        }

        return $results;
    }

    private function mapHeroicTypeToField(string $type): string
    {
        return match($type) {
            'SCIENTIST' => 'knowledge',
            'RULER'     => 'power',
            'GENERAL'   => 'power',
            'MERCHANT'  => 'wealth',
            'PROPHET'   => 'meaning',
            'ARTIST'    => 'status',
            default     => 'survival'
        };
    }
}
