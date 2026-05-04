<?php

namespace App\Modules\Simulation\Actions;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;

class ExportWorldAction
implements \App\Contracts\ActionInterface {
    /**
     * Export a World and its Universes to a serializable array.
     */
    public function execute(string $worldId): array
    {
        $world = World::with(['universes'])->findOrFail($worldId);

        return [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'world' => [
                'name' => $world->name,
                'slug' => $world->slug,
                'axiom' => $world->axiom,
                'world_seed' => $world->world_seed,
                'origin' => $world->origin,
                'current_genre' => $world->current_genre,
                'base_genre' => $world->base_genre,
                'active_genre_weights' => $world->active_genre_weights,
                'is_autonomic' => $world->is_autonomic,
                'global_tick' => $world->global_tick,
                'is_chaotic' => $world->is_chaotic,
            ],
            'universes' => $world->universes->map(function (Universe $u) {
                return [
                    'id' => $u->id,
                    'parent_universe_id' => $u->parent_universe_id,
                    'name' => $u->name,
                    'status' => $u->status,
                    'current_tick' => $u->current_tick,
                    'level' => $u->level,
                    'epoch' => $u->epoch,
                    'state_vector' => $u->state_vector,
                    'observation_load' => $u->observation_load,
                    'observer_bonus' => $u->observer_bonus,
                    'structural_coherence' => $u->structural_coherence,
                    'entropy' => $u->entropy,
                ];
            })->toArray(),
        ];
    }
}

