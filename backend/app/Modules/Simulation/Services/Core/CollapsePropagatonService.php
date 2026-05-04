<?php

namespace App\Modules\Simulation\Services\Core;

use App\Modules\World\Models\Universe;
use App\Modules\World\Models\UniverseBridge;
use Illuminate\Support\Facades\Log;

class CollapsePropagatonService
{
    /**
     * Propagate collapse from a target universe back to its source universes
     * via active bridges. Returns an array of effects to be applied to the sources,
     * or triggers the update directly if called globally.
     */
    public function propagate(Universe $collapsingUniverse, int $currentTick): array
    {
        // Only propagate if the universe is truly collapsing
        if ($collapsingUniverse->entropy <= 0.95 || $collapsingUniverse->stability_index >= 0.15) {
            return [];
        }

        // Find all active bridges WHERE target_universe_id = $collapsingUniverse->id
        $bridges = UniverseBridge::with('sourceUniverse')
            ->where('target_universe_id', $collapsingUniverse->id)
            ->where('is_active', true)
            ->get();

        if ($bridges->isEmpty()) {
            return [];
        }

        $propagations = [];

        foreach ($bridges as $bridge) {
            /** @var Universe $source */
            $source = $bridge->sourceUniverse;
            if (!$source) {
                continue;
            }

            // Calculate bleed entropy
            $bleedEntropy = $bridge->resonance_level * 0.1;

            // Log propagation
            Log::warning("Collapse bleed from Universe {$collapsingUniverse->id} to Universe {$source->id} (Bleed: {$bleedEntropy})");

            $propagations[] = [
                'source_universe_id' => $source->id,
                'bleed_entropy' => $bleedEntropy,
                'bridge_type' => $bridge->bridge_type,
            ];
            
            // Note: actual persistence is delegated to the caller or handled in a job
        }

        return $propagations;
    }
}


