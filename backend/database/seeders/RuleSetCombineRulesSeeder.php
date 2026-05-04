<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RuleSetCombineRulesSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            // Example: Realistic + Wuxia -> Wuxia wins on Physics
            [
                'ruleset_a_id' => 'realistic_medieval',
                'ruleset_b_id' => 'wuxia_basic',
                'conflict_strategy' => 'stricter_wins', // Realistic physics usually stricter
                'dimension_overrides' => json_encode([
                    "physics" => "wuxia_basic", // Magic/Qi physics takes precedence
                    "social_constraints" => "realistic_medieval"
                ]),
                'hybrid_outcome_id' => 'wuxia_basic'
            ],
            // Example: Wuxia + Xianxia -> Xianxia wins
            [
                'ruleset_a_id' => 'wuxia_high',
                'ruleset_b_id' => 'xianxia_classical',
                'conflict_strategy' => 'weighted_blend',
                'dimension_overrides' => json_encode([
                    "metaphysics" => "xianxia_classical",
                    "power_law" => "xianxia_classical"
                ]),
                'hybrid_outcome_id' => 'xianxia_classical'
            ]
        ];

        foreach ($rules as $rule) {
            DB::table('ruleset_combine_rules')->updateOrInsert(
                [
                    'ruleset_a_id' => $rule['ruleset_a_id'],
                    'ruleset_b_id' => $rule['ruleset_b_id']
                ],
                $rule
            );
        }
    }
}
