<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CivilizationAttractorSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'name' => 'revolution',
                'description' => 'Bất bình đẳng cao, ổn định thấp — thúc đẩy bất ổn và hình thành mới.',
                'activation_rules' => [
                    ['key' => 'entropy', 'op' => '>=', 'value' => 0.5],
                    ['key' => 'stability_index', 'op' => '<=', 'value' => 0.45],
                ],
                'force_map' => ['unrest' => 0.8, 'formation' => 0.5, 'crisis' => 0.6],
                'decay_rate' => 0.02,
            ],
            [
                'name' => 'golden_age',
                'description' => 'Entropy thấp, ổn định cao — thúc đẩy thời hoàng kim và hình thành.',
                'activation_rules' => [
                    ['key' => 'entropy', 'op' => '<=', 'value' => 0.4],
                    ['key' => 'stability_index', 'op' => '>=', 'value' => 0.65],
                ],
                'force_map' => ['golden_age' => 0.8, 'formation' => 0.5],
                'decay_rate' => 0.02,
            ],
            [
                'name' => 'fragmentation',
                'description' => 'Entropy cao — thúc đẩy sụp đổ và ly khai.',
                'activation_rules' => [
                    ['key' => 'entropy', 'op' => '>=', 'value' => 0.7],
                ],
                'force_map' => ['collapse' => 0.8, 'secession' => 0.5, 'crisis' => 0.7],
                'decay_rate' => 0.02,
            ],
        ];

        foreach ($data as $row) {
            $insert = [
                'name' => $row['name'],
                'description' => $row['description'],
                'activation_rules' => json_encode($row['activation_rules']),
                'force_map' => json_encode($row['force_map']),
                'decay_rate' => $row['decay_rate'],
            ];
            DB::table('civilization_attractors')->updateOrInsert(
                ['name' => $row['name']],
                $insert
            );
        }
    }
}
