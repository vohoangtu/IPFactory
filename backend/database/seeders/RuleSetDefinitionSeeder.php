<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RuleSetDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $registry = app(\App\Modules\Simulation\Services\Cosmology\AxiomRegistry::class);

        $definitions = [
            // --- TIER 0: REALISTIC ---
            $this->makeDefinition($registry, 'realistic_prehistoric', 'Thời Tiền Sử', 0, ['realistic']),
            $this->makeDefinition($registry, 'realistic_ancient', 'Văn Minh Cổ Đại', 0, ['ancient'], 'realistic_prehistoric'),
            $this->makeDefinition($registry, 'realistic_medieval', 'Trung Cổ', 0, ['medieval'], 'realistic_ancient'),
            $this->makeDefinition($registry, 'realistic_modern', 'Thời Đại Hiện Đại', 0, ['modern']),

            // --- TIER 1-3: WUXIA ---
            $this->makeDefinition($registry, 'wuxia_basic', 'Võ Thuật Thuần Túy', 1, ['wuxia', 'tier1'], 'realistic_medieval'),
            $this->makeDefinition($registry, 'wuxia_jianghu', 'Thế Giới Võ Hiệp', 2, ['wuxia', 'tier2'], 'wuxia_basic'),
            $this->makeDefinition($registry, 'wuxia_high', 'Thế Giới Cao Võ', 3, ['wuxia', 'tier3'], 'wuxia_jianghu'),

            // --- TIER 4: XIANXIA & SCI-FI ---
            $this->makeDefinition($registry, 'xianxia_classical', 'Tiên Hiệp Cổ Điển', 4, ['xianxia', 'tier4'], 'wuxia_high'),
            $this->makeDefinition($registry, 'scifi_transhuman', 'Hậu Nhân Loại', 4, ['scifi', 'tier4'], 'realistic_modern'),

            // --- TIER 5: CELESTIAL & MYTHOLOGY ---
            $this->makeDefinition($registry, 'xianxia_heavenly_dao', 'Thiên Đạo Chi Thế', 5, ['xianxia', 'tier5'], 'xianxia_classical'),
            $this->makeDefinition($registry, 'mythology_greek', 'Thần Thoại Hy Lạp', 5, ['mythology', 'greek']),
            $this->makeDefinition($registry, 'anime_dragonball', 'Thế Giới Long Châu', 5, ['anime', 'dragonball']),

            // --- SPECIAL: ANIME & MAGITECH ---
            $this->makeDefinition($registry, 'anime_naruto', 'Thế Giới Ninja', 3, ['anime', 'naruto'], 'wuxia_high'),
            $this->makeDefinition($registry, 'magitech_eastern', 'Ma Pháp Cơ Giới', 4, ['magitech', 'eastern'], 'xianxia_classical'),
            $this->makeDefinition($registry, 'magitech_nano_resonance', 'Cộng Hưởng Nano', 4, ['magitech', 'nano'], 'realistic_modern'),
            $this->makeDefinition($registry, 'fantasy_classic', 'Fantasy Cổ Điển', 2, ['fantasy'], 'realistic_medieval'),

            // --- TIER 6-7: METAPHYSICAL ---
            $this->makeDefinition($registry, 'dao_absolute', 'Đạo Cảnh', 6, ['ultimate', 'dao']),
            $this->makeDefinition($registry, 'hongmeng_primordial', 'Hồng Mông Tiên Thiên', 7, ['void', 'chaos']),
        ];

        foreach ($definitions as $definition) {
            DB::table('ruleset_definitions')->updateOrInsert(
                ['id' => $definition['id']],
                $definition
            );
        }
    }

    private function makeDefinition($registry, string $id, string $name, int $tier, array $tags, ?string $extends = null): array
    {
        $tierLabels = [
            0 => 'Thực Tế', 1 => 'Võ Thuật', 2 => 'Kiếm Hiệp', 3 => 'Cao Võ',
            4 => 'Tiên Hiệp', 5 => 'Thiên Đạo', 6 => 'Đạo', 7 => 'Hồng Mông'
        ];

        $axiomMap = $registry->getDefaultMapForTier($tier);

        // Apply specific overrides that aren't yet in axioms.json or are unique to the ID
        $physics = $axiomMap['physics'] ?? [];
        if (str_contains($id, 'wuxia_jianghu')) $physics['gravity'] = 0.8;
        if (str_contains($id, 'wuxia_high')) $physics['gravity'] = 0.5;
        if (str_contains($id, 'xianxia')) $physics['gravity'] = 0.3;
        if (str_contains($id, 'nano_resonance')) {
            $physics['gravity'] = 0.8;
            $physics['molecular_resonance'] = true;
            $physics['ambient_nanites'] = true;
        }
        if ($id === 'dao_absolute') $physics['concept_governed'] = true;

        return [
            'id' => $id,
            'name' => $name,
            'extends' => $extends,
            'tier' => $tier,
            'tier_label' => $tierLabels[$tier],
            'priority' => 50,
            'weight' => 1.0,
            'tags' => json_encode($tags),
            'physics' => json_encode($physics),
            'energy_systems' => json_encode($this->getEnergy($id)),
            'metaphysics' => json_encode($axiomMap['metaphysics'] ?? []),
            'power_law' => json_encode($this->getPowerLaw($id)),
            'social_constraints' => json_encode($axiomMap['social'] ?? $this->getSocial($id)),
            'emergence_rules' => json_encode([]),
            'knowledge_system' => json_encode([]),
            'individual_access' => json_encode([]),
            'temporal_dynamics' => json_encode([]),
            'tier_ceiling' => json_encode(["max_entity_tier" => $tier]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function getEnergy(string $id): array {
        if (str_contains($id, 'realistic')) return [["id" => "calorie", "type" => "natural"]];
        if (str_contains($id, 'wuxia_jianghu')) return [["id" => "internal_qi", "type" => "internal"]];
        if (str_contains($id, 'xianxia')) return [["id" => "spiritual_qi", "type" => "universal"]];
        if (str_contains($id, 'fantasy')) return [["id" => "mana", "type" => "ambient"]];
        if (str_contains($id, 'nano_resonance')) return [["id" => "nano_flux", "type" => "molecular_resonance"]];
        if (str_contains($id, 'anime_naruto')) return [["id" => "chakra", "type" => "hybrid"]];
        if (str_contains($id, 'anime_dragonball')) return [["id" => "ki", "type" => "explosive"]];
        return [];
    }

    private function getPowerLaw(string $id): array {
        if (str_contains($id, 'realistic')) return ["model" => "linear", "peak_vs_mortal" => 2];
        if (str_contains($id, 'wuxia')) return ["model" => "low_exponential", "peak_vs_mortal" => 100];
        if (str_contains($id, 'xianxia')) return ["model" => "high_exponential", "peak_vs_mortal" => 1000000];
        if (str_contains($id, 'dragonball')) return ["model" => "infinite_growth", "peak_vs_mortal" => 1e15];
        return ["model" => "linear"];
    }

    private function getSocial(string $id): array {
        $base = [
            "viable_structures" => ["tribe", "kingdom"],
            "power_dictates_law" => true,
            "social_mobility_model" => "strength_based"
        ];
        if (str_contains($id, 'modern')) {
            $base["viable_structures"] = ["nation_state", "corporation"];
            $base["social_mobility_model"] = "meritocratic_capitalist";
        }
        return $base;
    }

    private function getKnowledge(string $id): array {
        if (str_contains($id, 'modern') || str_contains($id, 'scifi')) {
            return ["propagation" => ["base_rate" => 0.9, "channels" => [["type" => "digital"]]]];
        }
        if (str_contains($id, 'xianxia')) {
            return ["propagation" => ["base_rate" => 0.05, "channels" => [["type" => "jade_slip"]]]];
        }
        return ["propagation" => ["base_rate" => 0.2, "channels" => [["type" => "oral"]]]];
    }

    private function getAccess(string $id): array {
        if (str_contains($id, 'xianxia')) {
            return [
                "access_tiers" => [
                    ["threshold" => 0.9, "label" => "mortal"],
                    ["threshold" => 0.09, "label" => "cultivator"],
                    ["threshold" => 0.01, "label" => "ascended"]
                ]
            ];
        }
        return ["access_tiers" => [["threshold" => 1.0, "label" => "citizen"]]];
    }

    private function getTemporal(string $id): array {
        return ["shock_events" => [["event" => "epoch_transition", "probability" => 0.001]]];
    }

    private function getAscension(string $id): array {
        return ["conditions" => ["power_threshold_reached"]];
    }
}
