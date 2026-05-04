<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Effects\WorldStateUpdateEffect;
use App\Modules\Narrative\Models\Narrative;

/**
 * NarrativePropagationEngine – Manages the spread of narratives across zones.
 * 
 * Logic: Active narratives gain virality or lose it over time.
 * If a narrative is strong enough in a zone, it starts affecting local zone fields.
 */
class NarrativePropagationEngine implements SimulationEngine
{
    public function name(): string { return 'NarrativePropagationEngine'; }
    public function version(): string { return '1.0.0'; }
    public function phase(): string { return 'META'; }
    public function priority(): int { return 200; }
    public function priorityCategory(): string { return 'STOCHASTIC'; }
    public function tickRate(): int { return 1; }
    public function isParallelSafe(): bool { return true; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $effects = [];
        $universeId = $ctx->getUniverseId();

        // Fetch active narratives for this universe
        $narratives = Narrative::where('universe_id', $universeId)
            ->where('is_active', true)
            ->get();

        foreach ($narratives as $narrative) {
            // Decay virality over time unless reinforced
            $newVirality = $narrative->virality * 0.95;
            
            if ($newVirality < 0.01) {
                $narrative->update(['is_active' => false]);
                continue;
            }

            $narrative->update(['virality' => $newVirality]);

            // If virality is high, it "leaks" into the global fields more strongly
            if ($newVirality > 0.5) {
               $effects[] = new WorldStateUpdateEffect($narrative->field_effects);
            }
        }

        return new EngineResult([], $effects);
    }
}

