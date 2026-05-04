<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Full 15 Core + 4 Meta Civilization Attractor Seeder
 *
 * 15 Core Attractors: Survival, Energy, Reproduction, Cooperation, Competition,
 *   Hierarchy, Trade, Knowledge, Technology, Culture, Meaning, Stability,
 *   Exploration, Innovation, Collapse
 *
 * 4 Meta Attractors: Complexity, Intelligence, Expansion, Transcendence
 *
 * force_map keys = event_types used by EventTriggerProcessor
 * field_bias = which of the 5 CivilizationFields this attractor amplifies
 */
class CivilizationAttractorFullSeeder extends Seeder
{
    public function run(): void
    {
        $attractors = [
            // ===============================
            // 15 CORE CIVILIZATION ATTRACTORS
            // ===============================
            [
                'name'             => 'survival',
                'description'      => 'Động lực sinh tồn cơ bản: tránh chết, đói, bệnh tật.',
                'activation_rules' => [['key' => 'entropy', 'op' => '>=', 'value' => 0.4]],
                'force_map'  => ['crisis' => 0.8, 'formation' => 0.4],
                'decay_rate' => 0.02,
                'field_bias' => 'survival',
            ],
            [
                'name'             => 'reproduction',
                'description'      => 'Duy trì nòi giống và dòng dõi. Family & Kinship.',
                'activation_rules' => [['key' => 'stability_index', 'op' => '>=', 'value' => 0.4]],
                'force_map'  => ['formation' => 0.6, 'golden_age' => 0.3],
                'decay_rate' => 0.015,
                'field_bias' => 'reproduction',
            ],
            [
                'name'             => 'wealth',
                'description'      => 'Tích lũy tài nguyên và sản xuất. Markets & Trade.',
                'activation_rules' => [['key' => 'resource_density', 'op' => '>=', 'value' => 0.4]],
                'force_map'  => ['trade' => 0.8, 'golden_age' => 0.5],
                'decay_rate' => 0.01,
                'field_bias' => 'wealth',
            ],
            [
                'name'             => 'power',
                'description'      => 'Kiểm soát và quyền lực chính trị. State & Empire.',
                'activation_rules' => [['key' => 'sci', 'op' => '>=', 'value' => 0.6]],
                'force_map'  => ['formation' => 0.7, 'battle' => 0.5],
                'decay_rate' => 0.01,
                'field_bias' => 'power',
            ],
            [
                'name'             => 'knowledge',
                'description'      => 'Tích lũy thông tin và công nghệ. Science & Learning.',
                'activation_rules' => [['key' => 'sci', 'op' => '>=', 'value' => 0.65]],
                'force_map'  => ['research' => 0.9, 'golden_age' => 0.7],
                'decay_rate' => 0.01,
                'field_bias' => 'knowledge',
            ],
            [
                'name'             => 'meaning',
                'description'      => 'Tìm kiếm ý nghĩa và căn tính. Religion & Philosophy.',
                'activation_rules' => [['key' => 'entropy', 'op' => '>=', 'value' => 0.5]],
                'force_map'  => ['meditate' => 0.8, 'ascension' => 0.6],
                'decay_rate' => 0.005,
                'field_bias' => 'meaning',
            ],
            [
                'name'             => 'status',
                'description'      => 'Vị thế xã hội và uy tín. Prestige & Fame.',
                'activation_rules' => [['key' => 'sci', 'op' => '>=', 'value' => 0.7]],
                'force_map'  => ['golden_age' => 0.5, 'crisis' => 0.3],
                'decay_rate' => 0.01,
                'field_bias' => 'status',
            ],
            [
                'name'             => 'belonging',
                'description'      => 'Nhu cầu thuộc về nhóm. Identity & Tribalism.',
                'activation_rules' => [['key' => 'stability_index', 'op' => '>=', 'value' => 0.5]],
                'force_map'  => ['formation' => 0.8, 'secession' => 0.4],
                'decay_rate' => 0.01,
                'field_bias' => 'belonging',
            ],
            [
                'name'             => 'collapse',
                'description'      => 'Lực sụp đổ khi hệ thống quá tải entropy.',
                'activation_rules' => [
                    ['key' => 'entropy', 'op' => '>=', 'value' => 0.8],
                    ['key' => 'stability_index', 'op' => '<=', 'value' => 0.3],
                ],
                'force_map'  => ['collapse' => 1.0, 'crisis' => 0.9],
                'decay_rate' => 0.03,
                'field_bias' => null,
            ],

            // ========================
            // 4 META CIVILIZATION ATTRACTORS
            // ========================
            [
                'name'             => 'meta_complexity',
                'description'      => '[META] Lực khiến civilization tăng độ phức tạp: tribe → city → empire → digital.',
                'activation_rules' => [
                    ['key' => 'sci', 'op' => '>=', 'value' => 0.8],
                    ['key' => 'stability_index', 'op' => '>=', 'value' => 0.65],
                    ['key' => 'entropy', 'op' => '<=', 'value' => 0.4],
                ],
                'force_map'  => ['golden_age' => 1.0, 'ascension' => 0.8, 'formation' => 0.6],
                'decay_rate' => 0.005,
                'field_bias' => 'knowledge',
            ],
            [
                'name'             => 'meta_intelligence',
                'description'      => '[META] Civilization hướng tới trí tuệ cao hơn: collective → artificial → superintelligence.',
                'activation_rules' => [
                    ['key' => 'sci', 'op' => '>=', 'value' => 0.85],
                    ['key' => 'entropy', 'op' => '<=', 'value' => 0.35],
                ],
                'force_map'  => ['ascension' => 1.0, 'golden_age' => 0.8],
                'decay_rate' => 0.005,
                'field_bias' => 'knowledge',
            ],
            [
                'name'             => 'meta_expansion',
                'description'      => '[META] Civilization mở rộng không gian sống: territorial → colonial → interplanetary.',
                'activation_rules' => [
                    ['key' => 'stability_index', 'op' => '>=', 'value' => 0.7],
                    ['key' => 'sci', 'op' => '>=', 'value' => 0.7],
                    ['key' => 'entropy', 'op' => '<=', 'value' => 0.4],
                ],
                'force_map'  => ['secession' => 0.4, 'formation' => 0.8, 'golden_age' => 0.7],
                'decay_rate' => 0.005,
                'field_bias' => 'survival',
            ],
            [
                'name'             => 'meta_transcendence',
                'description'      => '[META] Civilization vượt qua giới hạn sinh học: biological → cybernetic → digital → energy-based.',
                'activation_rules' => [
                    ['key' => 'sci', 'op' => '>=', 'value' => 0.9],
                    ['key' => 'stability_index', 'op' => '>=', 'value' => 0.8],
                    ['key' => 'entropy', 'op' => '<=', 'value' => 0.2],
                ],
                'force_map'  => ['ascension' => 1.0, 'eschaton' => 0.5],
                'decay_rate' => 0.002,
                'field_bias' => 'meaning',
            ],
        ];

        foreach ($attractors as $row) {
            $insert = [
                'name'             => $row['name'],
                'description'      => $row['description'],
                'activation_rules' => json_encode($row['activation_rules']),
                'force_map'        => json_encode($row['force_map']),
                'decay_rate'       => $row['decay_rate'],
            ];
            DB::table('civilization_attractors')->updateOrInsert(
                ['name' => $row['name']],
                $insert
            );
        }

        $this->command?->info('✅ CivilizationAttractorFullSeeder: 15 core + 4 meta attractors seeded.');
    }
}
