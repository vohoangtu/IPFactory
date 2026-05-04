<?php

namespace App\Modules\Simulation\Services\Meta;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseInteraction;
use Illuminate\Support\Facades\Log;

class MultiverseSynthesisService
{
    /**
     * Minimum resonance level required to converge two universes
     */
    const MERGE_THRESHOLD = 0.85;

    public function __construct(
        protected \App\Modules\Intelligence\Services\AI\AnalyticalAiService $analyticalAi
    ) {}

    /**
     * Check how well two universes match to authorize a convergence execution.
     */
    public function checkResonance(Universe $u1, Universe $u2): float
    {
        if ($u1->world_id !== $u2->world_id) {
            return 0.0; // Cannot merge different worlds
        }

        $latest1 = $u1->snapshots()->orderByDesc('tick')->first();
        $latest2 = $u2->snapshots()->orderByDesc('tick')->first();

        if (!$latest1 || !$latest2) {
            return 0.0;
        }

        $entropy1 = $latest1->entropy ?? 0;
        $entropy2 = $latest2->entropy ?? 0;
        
        // Similar entropy provides foundational stability for merging
        $entropySim = 1.0 - abs($entropy1 - $entropy2);
        
        // We use the Analytical AI (if available) to calculate cosine similarity of their 17D vectors or knowledge cores
        $vectorSim = $this->analyticalAi->calculateSimilarity($latest1->state_vector, $latest2->state_vector);

        $resonance = ($entropySim * 0.3) + ($vectorSim * 0.7);

        return max(0.0, min(1.0, $resonance));
    }

    /**
     * Converge two timelines into a Prime timeline (merge).
     */
    public function mergeUniverses(Universe $u1, Universe $u2, string $eventName = 'Great Convergence'): ?Universe
    {
         $resonance = $this->checkResonance($u1, $u2);
         if ($resonance < self::MERGE_THRESHOLD) {
             Log::info("Merge aborted: Resonance {$resonance} is below threshold " . self::MERGE_THRESHOLD);
             return null;
         }

         // Create a Prime universe
         $prime = Universe::create([
             'name' => "Prime: " . $u1->name . " + " . $u2->name,
             'world_id' => $u1->world_id,
             'saga_id' => $u1->saga_id,
             'parent_universe_id' => $u1->id, // Consider U1 as primary parent
             'metadata' => [
                 'is_prime' => true,
                 'merged_from' => [$u1->id, $u2->id],
                 'resonance_at_merge' => $resonance
             ]
         ]);

         // Record the interaction
         UniverseInteraction::create([
             'universe_a_id' => $u1->id,
             'universe_b_id' => $u2->id,
             'interaction_type' => 'convergence',
             'payload' => ['target_universe_id' => $prime->id, 'event' => $eventName],
             'resonance_level' => $resonance,
             'synchronicity_score' => $resonance
         ]);
         
         // In a full implementation, we would extract the KnowledgeCoreSignature of U1 and U2, 
         // combine them via Vector Addition, and create a Genesis Snapshot for the Prime universe.
         
         return $prime;
    }
}


