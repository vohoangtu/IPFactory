<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;

class SocietyAnalyzer
{
    /**
     * Checks triggers for emergent faction formation based on archetype ratios
     * and fragmented scores. Returns array of Faction configurations to spawn.
     */
    public function detectEmergentFactions(Universe $universe, array $archetypeRatios, float $fragmentedScore, \App\Support\Simulation\SimulationPRNG $rng): array
    {
        $newFactions = [];
        $axioms = $universe->axioms ?? [];
        $hasLinhKi = ($axioms['has_linh_ki'] ?? false) === true;
        $entropy = $universe->entropy ?? 0.5;

        // 1. Warrior dominance (Militaristic)
        $warriorRatio = $archetypeRatios['Chiến Binh'] ?? 0.0;
        $swordsmanRatio = $archetypeRatios['Kiếm Sĩ'] ?? 0.0;
        if (($warriorRatio + $swordsmanRatio) > 0.35) {
            $newFactions[] = [
                'name' => 'Quân Phiệt ' . $rng->nextInt(100, 999),
                'type' => 'militaristic',
                'description' => 'Một thế lực quân phiệt trỗi dậy từ sự áp đảo của các chiến binh.',
                'bias' => ['power' => 0.8, 'status' => 0.6, 'survival' => 0.4]
            ];
        }

        // 2. Axiom-based: Tu Chân Tông Môn (Sect)
        if ($hasLinhKi) {
            $cultivatorRatio = $archetypeRatios['Tu Chân Giả'] ?? 0.0;
            if ($cultivatorRatio > 0.10) {
                $newFactions[] = [
                    'name' => 'Tiên Môn ' . $rng->nextInt(100, 999),
                    'type' => 'spiritual',
                    'description' => 'Một tông môn tu tiên chính đạo được thành lập để tìm kiếm đạo quả.',
                    'bias' => ['meaning' => 0.9, 'knowledge' => 0.7, 'power' => 0.4]
                ];
            }

            // Ma Môn (Evil Sect)
            $evilRatio = $archetypeRatios['Tà Tu'] ?? 0.0;
            if ($evilRatio > 0.05 && $entropy > 0.7) {
                $newFactions[] = [
                    'name' => 'Ma Giáo ' . $rng->nextInt(100, 999),
                    'type' => 'predatory',
                    'description' => 'Một thế lực ma giáo trỗi dậy trong hỗn loạn, tôn thờ sức mạnh tuyệt đối.',
                    'bias' => ['power' => 1.0, 'chaos' => 0.8, 'survival' => 0.5]
                ];
            }
        }

        // 3. Ideology-based Factions (Phase 13)
        $topIdeology = \App\Modules\Narrative\Models\CulturalArtifact::where('universe_id', $universe->id)
            ->where('type', 'IDEOLOGY')
            ->where('is_active', true)
            ->orderByDesc('power_level')
            ->first();

        if ($topIdeology && $rng->nextInt(1, 100) > 80) {
            $newFactions[] = [
                'name' => 'Hội ' . $topIdeology->name,
                'type' => 'ideological',
                'description' => "Một tổ chức chính trị - xã hội được thành lập xung quanh tư tưởng '{$topIdeology->name}'.",
                'bias' => $topIdeology->properties['trait_modifiers'] ?? ['meaning' => 0.5]
            ];
        }

        // 4. Heavy fragmentation (Insurgent)
        if ($fragmentedScore > 0.6) {
            $newFactions[] = [
                'name' => 'Quân Cát Cứ ' . $rng->nextInt(100, 999), 
                'type' => 'insurgent',
                'description' => 'Một toán loạn quân hình thành trong thời kỳ suy vong.',
                'bias' => ['chaos' => 0.7, 'power' => 0.6, 'survival' => 0.8]
            ];
        }

        return $newFactions;
    }


    /**
     * Store newly formed factions into the Universe state vector.
     */
    public function storeFactions(Universe $universe, array $newFactions, int $tick, \App\Support\Simulation\SimulationPRNG $rng): void
    {
        if (empty($newFactions)) return;

        $stateVector = $universe->state_vector ?? [];
        $existingFactions = $stateVector['factions'] ?? [];

        foreach ($newFactions as $factionConfig) {
            if (count($existingFactions) >= 10) break;

            $existingFactions[] = [
                'id' => 'faction_' . hash('crc32', $factionConfig['name'] . $tick),
                'name' => $factionConfig['name'],
                'type' => $factionConfig['type'],
                'formed_at_tick' => $tick,
                'collective_decision_bias' => $factionConfig['bias'],
                'description' => $factionConfig['description'],
                'member_actor_ids' => [],
                'ideology_vector' => [0.5, 0.5, 0.5]
            ];
        }

        $stateVector['factions'] = $existingFactions;
        $universe->state_vector = $stateVector;
    }
}


