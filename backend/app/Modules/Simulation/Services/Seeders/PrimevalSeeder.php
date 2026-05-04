<?php

namespace App\Modules\Simulation\Services\Seeders;

use App\Contracts\Simulation\SeederInterface;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\Material;
use App\Modules\Simulation\Models\MaterialInstance;
use App\Modules\Narrative\Models\Chronicle;

class PrimevalSeeder implements SeederInterface
{
    public function supports(string $origin): bool
    {
        return strtolower($origin) === 'primeval';
    }

    public function seed(Universe $universe): void
    {
        $materials = [
            [
                'slug' => 'nguyen-huyet',
                'name' => 'Nguyên Huyết',
                'ontology' => 'biological',
                'description' => 'Dòng máu khởi nguyên chứa mã gene sần sùi và bền bỉ.',
                'pressure' => ['growth' => 0.3, 'stability' => 0.3, 'innovation' => -0.1]
            ],
            [
                'slug' => 'ban-thach-vinh-hang',
                'name' => 'Bàn Thạch Vĩnh Hằng',
                'ontology' => 'physical',
                'description' => 'Nền tảng của đất đai, vững chãi trước mọi biến động thời gian.',
                'pressure' => ['order' => 0.2, 'resistance' => 0.2, 'entropy' => -0.05]
            ]
        ];

        foreach ($materials as $m) {
            $model = Material::firstOrCreate(
                ['slug' => $m['slug']],
                [
                    'name' => $m['name'],
                    'ontology' => $m['ontology'],
                    'description' => $m['description'],
                    'pressure_coefficients' => $m['pressure']
                ]
            );

            MaterialInstance::create([
                'universe_id' => $universe->id,
                'material_id' => $model->id,
                'lifecycle' => 'active',
                'context' => [
                    'location' => ['x' => -5, 'y' => -5, 'z' => -5],
                    'quantity' => 150,
                    'origin' => 'Primeval'
                ]
            ]);
        }

        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => 0,
            'to_tick' => 0,
            'type' => 'myth',
            'raw_payload' => [
            'action' => 'legacy_event',
            'description' => 'Thế giới thức tỉnh trong sự vững chãi của đất đá và sự sôi sục của dòng máu nguyên bản. Một hành trình tiến hóa bền bỉ đã bắt đầu.'
        ]
        ]);
    }
}


