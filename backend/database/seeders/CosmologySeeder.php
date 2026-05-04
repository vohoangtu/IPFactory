<?php

namespace Database\Seeders;

use App\Modules\World\Models\Multiverse;
use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use App\Modules\Simulation\Services\Core\ImplicitOrchestratorService;
use Illuminate\Database\Seeder;

class CosmologySeeder extends Seeder
{
    /**
     * Seed default Cosmology: one Multiverse, one World, one Saga, one Universe.
     * Run: php artisan db:seed --class=CosmologySeeder
     */
    public function run(): void
    {
        $multiverse = Multiverse::firstOrCreate(
            ['slug' => 'default'],
            [
                'name' => 'Default Multiverse',
                'config' => ['description' => 'WorldOS V6 demo'],
            ]
        );

        $world = World::firstOrCreate(
            ['slug' => 'default-world'],
            [
                'multiverse_id' => $multiverse->id,
                'name' => 'Default World',
                'axiom' => [
                    'entropy_conservation' => true,
                    'material_organization' => true,
                ],
                'world_seed' => ['archetypes' => []],
                'origin' => 'Vietnamese',
                'is_autonomic' => true, // World tự tiến hóa theo scheduler (worldos:autonomic-pulse)
                'current_genre' => 'historical',
                'base_genre' => 'historical',
                'global_tick' => 0,
            ]
        );

        $orchestrator = app(ImplicitOrchestratorService::class);
        $existingUniverse = Universe::where('world_id', $world->id)->first();
        if (! $existingUniverse) {
            $orchestrator->spawnUniverse($world);
        }

        $this->command?->info('Cosmology seeded: 1 Multiverse, 1 World, 1 Universe (Implicit Saga).');
    }
}
