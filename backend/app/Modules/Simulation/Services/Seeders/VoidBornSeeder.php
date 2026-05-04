<?php

namespace App\Modules\Simulation\Services\Seeders;

use App\Contracts\Simulation\SeederInterface;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\Material;
use App\Modules\Simulation\Models\MaterialInstance;
use App\Modules\Narrative\Models\Chronicle;

class VoidBornSeeder implements SeederInterface
{
    public function supports(string $origin): bool
    {
        return strtolower($origin) === 'void-born';
    }

    public function seed(Universe $universe): void
    {
        $materials = [
            [
                'slug' => 'bui-hu-vo',
                'name' => 'Bụi Hư Vô',
                'ontology' => 'physical',
                'description' => 'Vật chất tối còn sót lại từ vụ nổ khởi nguyên, chứa đựng tiềm năng hỗn mang.',
                'pressure' => ['entropy' => 0.2, 'innovation' => 0.1, 'stability' => -0.1]
            ],
            [
                'slug' => 'tan-so-hu-ao',
                'name' => 'Tần số Hư Ảo',
                'ontology' => 'symbolic',
                'description' => 'Một loại sóng âm không thuộc về thực tại này, dẫn dắt ý thức vào cõi mộng.',
                'pressure' => ['spirituality' => 0.3, 'stability' => -0.05, 'order' => -0.1]
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
                    'location' => ['x' => 0, 'y' => 0, 'z' => 0],
                    'quantity' => 50,
                    'origin' => 'Void-Born'
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
            'description' => 'Từ trong vực thẳm của Hư Vô, những mảnh vỡ của bóng tối đã tụ lại. Một thế giới kỳ dị và mờ ảo bắt đầu thành hình.'
        ]
        ]);
    }
}


