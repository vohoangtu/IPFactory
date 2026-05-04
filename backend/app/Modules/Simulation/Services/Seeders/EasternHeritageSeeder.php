<?php

namespace App\Modules\Simulation\Services\Seeders;

use App\Contracts\Simulation\SeederInterface;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\Material;
use App\Modules\Simulation\Models\MaterialInstance;
use App\Modules\Narrative\Models\Chronicle;

class EasternHeritageSeeder implements SeederInterface
{
    public function supports(string $origin): bool
    {
        return in_array(strtolower($origin), ['eastern', 'chinese']);
    }

    public function seed(Universe $universe): void
    {
        $materials = [
            [
                'slug' => 'mandate-of-heaven',
                'name' => 'Mandate of Heaven',
                'ontology' => 'symbolic',
                'description' => 'The idea that there could be only one legitimate ruler.',
                'pressure' => ['authority' => 1.0, 'stability' => 0.6]
            ],
        ];

        foreach ($materials as $m) {
            $model = Material::firstOrCreate(
                ['slug' => $m['slug']],
                ['name' => $m['name'], 'ontology' => $m['ontology'], 'description' => $m['description'], 'pressure_coefficients' => $m['pressure']]
            );

            MaterialInstance::create([
                'universe_id' => $universe->id,
                'material_id' => $model->id,
                'lifecycle' => 'active',
                'context' => ['quantity' => 90, 'location' => ['x' => 20, 'y' => 20, 'z' => 0], 'origin' => 'Eastern']
            ]);
        }

        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => 0,
            'to_tick' => 0,
            'type' => 'myth',
            'raw_payload' => [
            'action' => 'legacy_event',
            'description' => 'The Dragon has awoken, and the Mandate of Heaven is bestowed.'
        ]
        ]);
    }
}


