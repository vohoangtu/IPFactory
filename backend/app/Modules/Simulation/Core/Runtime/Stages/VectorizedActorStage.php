<?php

namespace App\Modules\Simulation\Core\Runtime\Stages;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Runtime\Contracts\SimulationStageInterface;
use App\Modules\Simulation\Services\Core\FfiActorEngine;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

/**
 * Phase 75: Vectorized Actor Stage (The Zenith Performance) 🚀
 * 
 * Uses Rayon-parallelized Rust FFI to process thousands of actors' physical 
 * and behavioral traits (hunger, energy, fear, trauma) in milliseconds.
 */
class VectorizedActorStage implements SimulationStageInterface
{
    public function __construct(
        protected FfiActorEngine $ffiActorEngine,
        protected \App\Modules\Simulation\Core\Runtime\State\StateManager $stateManager
    ) {}

    public function run(Universe $universe, int $tick, ?UniverseSnapshot $savedSnapshot = null, array $context = []): void
    {
        $state = $this->stateManager->get();
        if (!$state) {
            return;
        }

        $actors = $state->getActorEntities();
        $alive = array_filter($actors, fn($a) => $a->isAlive);
        if (empty($alive)) {
            return;
        }

        // 1. Prepare SoA (Struct-of-Arrays) buffers
        $ids = [];
        $zoneIds = [];
        $hunger = [];
        $energy = [];
        $fear = [];
        $trauma = [];
        $heroicTypes = [];
        $lineageIds = [];
        $memes = [];
        $archetypes = [];
        $traitsMatrix = [];

        foreach ($alive as $actor) {
            $m = $actor->metrics ?? [];
            $ids[] = $actor->id ?? 0;
            $zoneIds[] = (int)($m['zone_id'] ?? 0);
            $hunger[] = (float)($m['needs']['hunger'] ?? 0.5);
            $energy[] = (float)($m['energy'] ?? 100.0) / 100.0; // Normalize
            $fear[] = (float)($m['needs']['safety'] ?? 0.5);
            $trauma[] = (float)($m['trauma'] ?? 0.0); // Persistent memory trait
            $heroicTypes[] = (int)($actor->isHeroic ? 1 : 0);
            $lineageIds[] = (int)($actor->lineage_id ?? 0);
            $memes[] = (int) ($m['meme_mask'] ?? 0);
            $archetypes[] = $actor->archetype ?? 'Commoner';
            // 17-element trait vector per actor
            $tv = data_get($m, 'trait_vector');
            if (is_array($tv) && count($tv) === 17) {
                foreach ($tv as $v) { $traitsMatrix[] = (float) $v; }
            } else {
                for ($i = 0; $i < 17; $i++) { $traitsMatrix[] = 0.5; }
            }
        }

        // 2. Execute Parallel Rust FFI (Zenith Logic)
        try {
            $results = $this->ffiActorEngine->processActorsSoa(
                $tick,
                $ids,
                $zoneIds,
                $hunger,
                $energy,
                $fear,
                $trauma,
                $heroicTypes,
                $lineageIds,
                $memes,
                $traitsMatrix,
                [],
                [],
                $archetypes
            );

            // 3. Update State Manifold back from vectorized results
            $idx = 0;
            foreach ($alive as $actor) {
                if (!isset($results[$idx])) {
                    $idx++;
                    continue;
                }

                $res = $results[$idx];
                $m = $actor->metrics ?? [];
                
                // Map results back to actor metrics
                $m['needs']['hunger'] = $res['new_hunger'];
                $m['energy'] = $res['new_energy'] * 100.0; // Denormalize
                $m['trauma'] = $res['new_trauma']; // Persistence updated!
                $m['behavior_state_id'] = $res['action_id'];
                
                // Simple action mapping
                $m['behavior_state'] = match($res['action_id']) {
                    1 => 'eating',
                    2 => 'fleeing',
                    default => 'idle'
                };

                $actor->metrics = $m;
                $idx++;
            }

            Log::debug("VectorizedActorStage: Processed " . count($alive) . " actors via Rust FFI (Rayon Parallel)");

        } catch (\Throwable $e) {
            Log::error("VectorizedActorStage FFI Fail: " . $e->getMessage());
        }
    }
}



