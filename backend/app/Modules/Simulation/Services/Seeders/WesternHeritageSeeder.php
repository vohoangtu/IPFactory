<?php

namespace App\Modules\Simulation\Services\Seeders;

use App\Contracts\Simulation\SeederInterface;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\Material;
use App\Modules\Simulation\Models\MaterialInstance;
use App\Modules\Narrative\Models\Chronicle;

class WesternHeritageSeeder implements SeederInterface
{
    public function supports(string $origin): bool
    {
        return in_array(strtolower($origin), ['western', 'european']);
    }

    public function seed(Universe $universe): void
    {
        $materials = [
            [
                'slug' => 'feudal-contract',
                'name' => 'Feudal Contract',
                'ontology' => 'institutional',
                'description' => 'A hierarchical system of land ownership and duties.',
                'pressure' => ['order' => 0.4, 'innovation' => -0.2, 'entropy' => -0.1]
            ],
            [
                'slug' => 'code-of-chivalry',
                'name' => 'Code of Chivalry',
                'ontology' => 'symbolic',
                'description' => 'Moral system which goes beyond rules of combat.',
                'pressure' => ['order' => 0.1, 'stability' => 0.3, 'growth' => -0.05]
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
                'context' => ['quantity' => 80, 'location' => ['x' => 10, 'y' => 10, 'z' => 0], 'origin' => 'Western']
            ]);
        }

        Chronicle::create([
            'universe_id' => $universe->id,
            'from_tick' => 0,
            'to_tick' => 0,
            'type' => 'myth',
            'raw_payload' => [
            'action' => 'legacy_event',
            'description' => 'The foundations of the Realm have been laid, bound by oath and iron.'
        ]
        ]);
    }
}


