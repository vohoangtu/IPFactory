<?php

namespace App\Modules\Simulation\Services\Seeders;

use App\Contracts\Simulation\SeederInterface;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\Material;
use App\Modules\Simulation\Models\MaterialInstance;
use App\Modules\Simulation\Models\MaterialReaction;
use App\Modules\Narrative\Models\Chronicle;

class VietnameseHeritageSeeder implements SeederInterface
{
    public function supports(string $origin): bool
    {
        return strtolower($origin) === 'vietnamese';
    }

    public function seed(Universe $universe): void
    {
        // 1. Core Materials
        $materials = [
            [
                'slug' => 'nong-nghiep-lua-nuoc',
                'name' => 'Nông nghiệp Lúa nước',
                'ontology' => 'institutional',
                'description' => 'Nền tảng của sự ổn định và cộng đồng làng xã.',
                'pressure' => ['order' => 0.2, 'growth' => 0.1, 'entropy' => 0.05]
            ],
            [
                'slug' => 'tho-cung-to-tien',
                'name' => 'Thờ cúng Tổ tiên',
                'ontology' => 'symbolic',
                'description' => 'Sợi dây liên kết tâm linh xuyên thế hệ.',
                'pressure' => ['order' => 0.3, 'innovation' => -0.1, 'stability' => 0.1]
            ],
            [
                'slug' => 'thuy-loi-so-khai',
                'name' => 'Thủy lợi Sơ khai',
                'ontology' => 'physical',
                'description' => 'Hệ thống đê điều và kênh rạch buổi đầu.',
                'pressure' => ['order' => 0.1, 'growth' => 0.2, 'entropy' => 0.1]
            ]
        ];

        $materialModels = [];
        foreach ($materials as $m) {
            $model = Material::firstOrCreate(
                ['slug' => $m['slug']],
                [
                    'name' => $m['name'],
                    'ontology' => $m['ontology'],
                    'description' => $m['description'],
                    'pressure_coefficients' => $m['pressure'],
                    'lifecycle' => 'dormant'
                ]
            );
            $materialModels[$m['slug']] = $model;
        }

        // 2. Initial Instances (Starting materials are Active)
        $startingSlugs = ['nong-nghiep-lua-nuoc', 'tho-cung-to-tien'];
        foreach ($startingSlugs as $slug) {
            MaterialInstance::create([
                'universe_id' => $universe->id,
                'material_id' => $materialModels[$slug]->id,
                'lifecycle' => 'active',
                'context' => ['origin' => 'Vietnamese']
            ]);
        }

        // 3. Material Reactions (§8.4)
        // Nong nghiep lua nuoc -> Thuy loi so khai
        MaterialReaction::firstOrCreate([
            'slug' => 'evolution-thuy-loi-so-khai',
        ], [
            'name' => 'Tiến hóa Thủy lợi Sơ khai',
            'inputs' => ['nong-nghiep-lua-nuoc' => 1],
            'outputs' => [
                'nong-nghiep-lua-nuoc' => 1, // Catalyst: remains
                'thuy-loi-so-khai' => 1
            ],
            'condition' => 'field_order > 0.3',
            'rate' => 0.05,
            'energy_cost' => 10.0
        ]);

        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => 0,
            'to_tick' => 0,
            'type' => 'myth',
            'raw_payload' => [
            'action' => 'legacy_event',
            'description' => 'Hạt giống của văn minh Lạc Việt đã được gieo xuống, mang theo hơi thở của đất và hồn của tổ tiên.'
        ]
        ]);
    }
}


