<?php

namespace App\Modules\Simulation\Services\Meta;

use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use App\Modules\Intelligence\Actions\OvermindEvolutionAction;
use App\Modules\Simulation\Services\Cosmology\CosmogenesisService;
use App\Modules\Simulation\Services\Ecology\SurvivalPruningService;
use Illuminate\Support\Facades\Log;

/**
 * MultiverseSovereigntyService: The 'Grand Orchestrator' belonging to V10.
 * Ensures total autonomy by managing Evolution, Birth, and Death in one loop.
 */
class MultiverseSovereigntyService
{
    public function __construct(
        protected OvermindEvolutionAction $evolution,
        protected CosmogenesisService $cosmogenesis,
        protected SurvivalPruningService $pruning
    ) {}

    /**
     * Run the total sovereignty loop for a universe.
     */
    public function orchestrate(Universe $universe, array $events): void
    {
        if (!$universe->world || !$universe->world->is_autonomic) return;

        // 1. Birth: Check Rust events for Cosmogenesis
        $this->cosmogenesis->handleEvents($universe, $events);

        // 2. Evolution: Issue auto-edicts if stagnating
        $this->evolution->execute($universe);

        // 3. Death: Prune if SCI is too low
        $this->pruningAfterPulse($universe);
    }

    protected function pruningAfterPulse(Universe $universe): void
    {
        $latest = $universe->snapshots()->orderByDesc('tick')->first();
        if (!$latest) return;

        $sci = $latest->metrics['sci'] ?? 1.0;
        if ($sci < 0.2) {
             $universe->update(['status' => 'collapsed']);
             Log::warning("SOVEREIGNTY: Universe #{$universe->id} collapsed autonomously (SCI: {$sci})");
             
             // 4. Recycling: Extract knowledge to feed the Multiverse
             $this->recycleKnowledge($universe, $latest);
        }
    }

    protected function recycleKnowledge(Universe $universe, $snapshot): void
    {
        $knowledge = ($snapshot->state_vector ?? [])['knowledge_core'] ?? 0.0;
        if ($knowledge > 0.3) {
            Log::info("SOVEREIGNTY: Recycling {$knowledge} knowledge from collapsed Universe #{$universe->id}");
            // Store this in Multiverse metadata or World legacy for next spawn
            $world = $universe->world;
            if (!$world) return;
            $legacy = $world->world_seed ?? [];
            $legacy['inspiration_pool'] = ($legacy['inspiration_pool'] ?? 0.0) + ($knowledge * 0.1);
            $world->update(['world_seed' => $legacy]);
        }
    }
}




