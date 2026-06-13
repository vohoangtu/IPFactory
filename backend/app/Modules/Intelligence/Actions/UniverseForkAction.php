<?php

namespace App\Modules\Intelligence\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Contracts\UniverseRepositoryInterface;
use App\Modules\Intelligence\Actions\UniverseMutationAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Phase 32: Darwinian Forking Action.
 * Creates a child universe with a mutated genome from a high-fitness parent.
 */
class UniverseForkAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        private readonly UniverseMutationAction $mutationAction
    ) {}

    public function execute(Universe $parent, int $tick): ?Universe
    {
        return DB::transaction(function () use ($parent, $tick) {
            // 1. Create the child universe
            $child = new Universe();
            $child->world_id = $parent->world_id;
            $child->multiverse_id = $parent->multiverse_id;
            $child->parent_universe_id = $parent->id;
            $child->name = $parent->name . " (Fork " . dechex(mt_rand(0x100, 0xFFF)) . ")";
            $child->seed = mt_rand(1, 2147483647);
            $child->current_tick = $tick;
            $child->level = $parent->level;
            $child->status = 'active';
            $child->state_vector = $parent->state_vector;
            $child->engine_manifest = $parent->engine_manifest;
            $child->observation_load = 0.0;
            $child->observer_bonus = $parent->observer_bonus;
            $child->structural_coherence = $parent->structural_coherence;
            $child->entropy = $parent->entropy;
            $child->kernel_genome = $parent->kernel_genome;
            $child->fitness_score = $parent->fitness_score;
            $child->forked_at_tick = $tick;
            
            $child->save();

            // 2. Mutate the genome of the child
            // We use a temporary SimulationPRNG or similar for determinism if needed, 
            // but forking is a meta-event.
            $rng = \App\Support\Simulation\SimulationPRNG::forUniverse($child);
            $this->mutationAction->mutate($child, $rng);

            Log::info("MULTIVERSE: Universe #{$parent->id} forked to #{$child->id} (Fitness: {$parent->fitness_score})");

            return $child;
        });
    }
}


