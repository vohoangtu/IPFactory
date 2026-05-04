<?php

namespace App\Modules\Simulation\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Services\Meta\ParadoxResolver;
use App\Modules\Simulation\Services\Society\SoulAnchorService;
use App\Modules\Simulation\Services\Meta\MultiverseInteractionService;
use Illuminate\Support\Facades\Log;

/**
 * MergeUniversesAction: Executes the physical re-convergence of two realities (§V15).
 */
class MergeUniversesAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected ParadoxResolver $resolver,
        protected SoulAnchorService $soulAnchor
    ) {}

    /**
     * Merge two universes. One will absorb the other.
     */
    public function execute(Universe $a, Universe $b): void
    {
        Log::warning("UNITY: Executing re-convergence between #{$a->id} and #{$b->id}");

        $resolution = $this->resolver->resolve($a, $b);
        $master = $resolution['master'];
        $slave = $resolution['slave'];

        // 1. Transfer or Sync data from slave to master if needed
        // For simplicity in V15, the Master state overrides the Slave.
        // But we preserve the Chronicle to maintain narrative continuity.
        
        Chronicle::create([
            'universe_id' => $master->id,
            'from_tick' => $master->current_tick,
            'to_tick' => $master->current_tick,
            'type' => 'convergence',
            'raw_payload' => [
                'action' => 'legacy_event',
                'description' => $resolution['synthesis']
            ],
        ]);

        // 2. Transmute Slave state into Essence (§V17)
        $this->harvestEssence($slave);

        // Phase 99: Narrative Continuity (§V22)
        // Anchor transcendental souls before the universe collapses
        $this->soulAnchor->anchorSouls($slave);

        // 3. Halt the slave universe
        $slave->update([
            'status' => 'halted',
            'halt_reason' => "Re-convergence with Universe #{$master->id}"
        ]);

        // Reincarnate anchored souls into the master universe
        $this->soulAnchor->reincarnateSouls($master);

        Log::info("Cosmogenesis: Universe #{$slave->id} has been absorbed by #{$master->id}. Unity achieved.");
    }

    protected function harvestEssence(Universe $universe): void
    {
        $essence = ($universe->structural_coherence + $universe->entropy) * 10;
        
        // Find demiurges aligned with legends in this universe
        $alignments = \App\Modules\Intelligence\Models\LegendaryAgent::where('universe_id', $universe->id)
            ->whereNotNull('alignment_id')
            ->pluck('alignment_id')
            ->unique();

        if ($alignments->isEmpty()) {
            // Distribute to all active demiurges as cosmic background radiation
            $rivals = \App\Modules\World\Models\Demiurge::where('is_active', true)->get();
            $share = $essence / max(1, $rivals->count());
            foreach ($rivals as $r) $r->increment('essence_pool', $share);
        } else {
            $share = $essence / $alignments->count();
            foreach ($alignments as $id) {
                \App\Modules\World\Models\Demiurge::find($id)?->increment('essence_pool', $share);
            }
        }

        Log::info("ESSENCE: Universe #{$universe->id} recycled into {$essence} Primal Essence.");
    }
}


