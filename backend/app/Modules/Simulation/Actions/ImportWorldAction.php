<?php

namespace App\Modules\Simulation\Actions;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportWorldAction
implements \App\Contracts\ActionInterface {
    /**
     * Import a World from a serialized array.
     */
    public function execute(array $data): World
    {
        return DB::transaction(function () use ($data) {
            $worldData = $data['world'];
            
            // Ensure unique slug
            $originalSlug = $worldData['slug'] ?? Str::slug($worldData['name']);
            $slug = $originalSlug . '-imported-' . Str::random(4);
            
            $world = World::create(array_merge($worldData, [
                'name' => $worldData['name'] . ' (Imported)',
                'slug' => $slug,
            ]));

            $universes = $data['universes'] ?? [];
            $idMapping = [];

            // Sort universes to ensure parents are created before children if they are sequential
            // Actually, we'll use a mapping to handle arbitrary order
            
            // First pass: Create universes without parent_universe_id
            foreach ($universes as $uData) {
                $oldId = $uData['id'];
                unset($uData['id'], $uData['parent_universe_id']);
                
                $universe = Universe::create(array_merge($uData, [
                    'world_id' => $world->id,
                ]));
                
                $idMapping[$oldId] = $universe->id;
            }

            // Second pass: Update parent_universe_id using the mapping
            foreach ($universes as $uData) {
                if (!empty($uData['parent_universe_id'])) {
                    $newId = $idMapping[$uData['id']];
                    $newParentId = $idMapping[$uData['parent_universe_id']] ?? null;
                    
                    if ($newParentId) {
                        Universe::where('id', $newId)->update([
                            'parent_universe_id' => $newParentId
                        ]);
                    }
                }
            }

            return $world;
        });
    }
}

