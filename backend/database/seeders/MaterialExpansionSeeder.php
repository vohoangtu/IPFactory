<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\World\Models\Material;
use App\Modules\Simulation\Models\MaterialReaction;

class MaterialExpansionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Define Materials (Base state nodes)
        $materials = [
            ['slug' => 'quang-nang-co-dai', 'name' => 'Quang Năng Cổ Đại', 'ontology' => 'physical', 'pressure_coefficients' => ['energy_level' => 0.4]],
            ['slug' => 'quang-nang-vinh-cuu', 'name' => 'Quang Năng Vĩnh Cửu', 'ontology' => 'symbolic', 'pressure_coefficients' => ['meaning' => 0.5]],
            ['slug' => 'quang-nang-nguyen-thuy', 'name' => 'Quang Năng Nguyên Thủy', 'ontology' => 'physical', 'pressure_coefficients' => ['energy_level' => 0.6]],
            ['slug' => 'vong-lap-hu-vo', 'name' => 'Vòng Lặp Hư Vô', 'ontology' => 'physical', 'pressure_coefficients' => ['entropy' => 0.9]],
        ];

        foreach ($materials as $m) {
            Material::updateOrCreate(['slug' => $m['slug']], $m);
        }

        // 2. Define Reactions (Directed edges / Field driven)
        $reactions = [
            [
                'slug' => 'eternal-solar-crystallization',
                'name' => 'Crystallization of Eternal Solar',
                'inputs' => ['quang-nang-co-dai' => 1],
                'outputs' => ['quang-nang-vinh-cuu' => 1],
                'condition' => 'rule "eternal" when knowledge > 0.8 then emit "REACTION_TRIGGERED" end',
                'rate' => 0.1,
                'energy_cost' => 10.0,
            ],
            [
                'slug' => 'primordial-decay',
                'name' => 'Primordial Decay to Void',
                'inputs' => ['quang-nang-nguyen-thuy' => 2],
                'outputs' => ['vong-lap-hu-vo' => 1],
                'condition' => 'rule "void" when entropy > 0.7 then emit "REACTION_TRIGGERED" end',
                'rate' => 0.2,
                'entropy_produced' => 0.1,
            ],
        ];

        foreach ($reactions as $r) {
            MaterialReaction::updateOrCreate(['slug' => $r['slug']], $r);
        }
    }
}
