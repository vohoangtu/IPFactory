<?php

namespace App\Modules\WorldOS\Actions;

use App\Contracts\ActionInterface;
use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateGenesisUniverseAction implements ActionInterface
{
    /**
     * Create a new World and its first Genesis Universe.
     *
     * @param array $data {
     *   name: string,
     *   slug: ?string,
     *   base_genre: string,
     *   axioms: array,
     *   initial_state: ?array
     * }
     * @return Universe
     */
    public function execute(mixed ...$args): mixed
    {
        return $this->doExecute($args[0]);
    }

    public function doExecute(array $data): Universe
    {
        return DB::transaction(function () use ($data) {
            $name = $data['name'];
            $slug = $data['slug'] ?? Str::slug($name) . '-' . Str::random(6);
            
            // 1. Create World
            $world = World::create([
                'name' => $name,
                'slug' => $slug,
                'base_genre' => $data['base_genre'] ?? 'fantasy',
                'current_genre' => $data['base_genre'] ?? 'fantasy',
                'axiom' => $data['axioms'] ?? [],
                'origin' => 'observer_genesis',
                'is_autonomic' => true,
            ]);

            // 2. Create Genesis Universe
            $universe = Universe::create([
                'world_id' => $world->id,
                'name' => "Genesis of " . $name,
                'status' => 'active',
                'current_tick' => 0,
                'entropy' => data_get($data, 'initial_state.entropy', 0.0),
                'structural_coherence' => 1.0,
                'state_vector' => $data['initial_state'] ?? [
                    'entropy' => 0.0,
                    'stability_index' => 1.0,
                    'metrics' => []
                ],
                'kernel_genome' => $data['axioms'] ?? [],
            ]);

            return $universe;
        });
    }
}
