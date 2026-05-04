<?php

namespace App\Modules\Simulation\Services\Seeders;

use App\Contracts\Simulation\SeederInterface;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\Material;
use App\Modules\Simulation\Models\MaterialInstance;
use App\Modules\Narrative\Models\Chronicle;

class SolarSeeder implements SeederInterface
{
    public function supports(string $origin): bool
    {
        return strtolower($origin) === 'solar';
    }

    public function seed(Universe $universe): void
    {
        $materials = [
            [
                'slug' => 'quang-nang-co-dai',
                'name' => 'Quang Năng Cổ Đại',
                'ontology' => 'physical',
                'description' => 'Năng lượng thuần khiết từ lõi của ngôi sao mẹ tối thượng.',
                'pressure' => ['energy_level' => 0.4, 'innovation' => 0.2, 'entropy' => -0.1]
            ],
            [
                'slug' => 'ban-thiet-ke-thai-duong',
                'name' => 'Bản thiết kế Thái Dương',
                'ontology' => 'institutional',
                'description' => 'Tri thức về sự hòa hợp giữa ánh sáng và cấu trúc xã hội.',
                'pressure' => ['order' => 0.3, 'growth' => 0.1, 'stability' => 0.2]
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
                    'location' => ['x' => 10, 'y' => 10, 'z' => 10],
                    'quantity' => 200,
                    'origin' => 'Solar'
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
            'description' => 'Ánh sáng vĩnh cửu của Thái Dương đã khai mở thực tại này. Mọi sự sống sẽ vươn lên dưới hào quang rực rỡ và trật tự tuyệt đối.'
        ]
        ]);
    }
}


