<?php

namespace App\Modules\Simulation\Services\Ecology;

use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

/**
 * SurvivalPruningService: Automatically deactivates universes with 
 * critically low Structural Coherence Index (SCI) (§V9).
 */
class SurvivalPruningService
{
    /**
     * Scan active universes and prune those that fail the survival threshold.
     */
    public function prune(float $threshold = 0.2): int
    {
        $count = 0;
        $universes = Universe::where('status', 'active')->get();

        foreach ($universes as $universe) {
            $latest = $universe->snapshots()->orderByDesc('tick')->first();
            if (!$latest) continue;

            $sci = $latest->metrics['sci'] ?? 1.0;
            
            if ($sci < $threshold) {
                $universe->update(['status' => 'collapsed']);
                Log::warning("SURVIVAL PRUNING: Universe #{$universe->id} collapsed. SCI: {$sci}");
                $count++;
            }
        }

        return $count;
    }
}


