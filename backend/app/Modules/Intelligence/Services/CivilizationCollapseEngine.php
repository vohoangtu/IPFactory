<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;
use App\Modules\World\Models\LegacyVault;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Phase 35: Civilization Collapse Engine.
 * Handles harvesting legacy data and archiving universes.
 */
class CivilizationCollapseEngine
{
    /**
     * Check if universe should collapse and execute if so.
     */
    public function checkAndExecute(Universe $universe, int $tick): bool
    {
        $entropy = (float)($universe->entropy ?? 0.5);
        $stability = (float)($universe->structural_coherence ?? 0.5);

        // Trigger: Entropy is significantly higher than stability
        if ($entropy > 2.2 * $stability && $stability < 0.15) {
            $this->collapse($universe, $tick);
            return true;
        }

        return false;
    }

    /**
     * Formal collapse process.
     */
    public function collapse(Universe $universe, int $tick): void
    {
        DB::transaction(function () use ($universe, $tick) {
            // 1. Extract Legacy Data
            $stateVector = $universe->state_vector ?? [];
            $metrics = $universe->metrics ?? [];
            
            $legacyData = [
                'final_tick' => $tick,
                'final_phase' => $stateVector['phase_score'] ?? [],
                'peak_tech_level' => $stateVector['historical_flags']['peak_tech_level'] ?? $universe->level,
                'historical_flags' => $stateVector['historical_flags'] ?? [],
                'factions' => $stateVector['factions'] ?? [],
                'metrics' => $metrics
            ];

            // 2. Save to Legacy Vault
            LegacyVault::create([
                'world_id' => $universe->world_id,
                'entity_name' => $universe->name . " (Legacy)",
                'entity_type' => 'civilization',
                'legacy_data' => $legacyData,
                'archived_at_tick' => $tick,
                'impact_score' => (float)($universe->fitness_score ?? 0.0)
            ]);

            // 3. Update Universe Status
            $universe->status = 'collapsed';
            $universe->save();

            // 4. Cleanup (Optional: soft delete actors or mark as dead)
            // For now, we just mark the universe as collapsed. 
            // The tick loop should skip collapsed universes.

            Log::warning("MULTIVERSE: Universe #{$universe->id} has COLLAPSED at tick {$tick}. Legacy archived.");
        });
    }

    public function evaluate(\App\Modules\World\Models\Universe $universe, \App\Modules\Simulation\Models\UniverseSnapshot $snapshot): void
    {
        $this->checkAndExecute($universe, (int) $snapshot->tick);
    }
}

