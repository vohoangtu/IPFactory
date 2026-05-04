<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Narrative\Models\CulturalArtifact;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;

/**
 * Layer 13: Cultural Influence Engine 🎭📡
 * 
 * "Di sản không chỉ là quá khứ, nó là bản chỉ dẫn cho tương lai."
 * This engine applies the influence of active Cultural Artifacts to individual actors.
 */
class CulturalInfluenceEngine implements SimulationEngine
{
    public function name(): string
    {
        return 'cultural_influence';
    }

    public function phase(): string
    {
        return 'meta';
    }

    public function priority(): int
    {
        return 13;
    }

    public function tickRate(): int
    {
        return 5; // Influence doesn't need to be calculated every tick
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function isParallelSafe(): bool
    {
        return true;
    }

    public function priorityCategory(): string
    {
        return 'STOCHASTIC';
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $universeId = $ctx->getUniverseId();
        
        // 1. Get active artifacts for this universe
        $activeArtifacts = CulturalArtifact::where('universe_id', $universeId)
            ->where('is_active', true)
            ->orderByDesc('power_level')
            ->take(5) // Limit to top 5 most influential works to avoid over-complication
            ->get();

        if ($activeArtifacts->isEmpty()) {
            return new EngineResult([], [], []);
        }

        // 2. Aggregate cultural pressure vector
        // This vector will influence the attractor field that determines actor motivations
        $culturalPressure = [
            'meaning' => 0.0,
            'power' => 0.0,
            'knowledge' => 0.0,
            'status' => 0.0,
            'wealth' => 0.0,
            'belonging' => 0.0,
            'survival' => 0.0,
            'chaos' => 0.0,
        ];

        foreach ($activeArtifacts as $artifact) {
            $impactCoefficients = $artifact->properties['trait_modifiers'] ?? [];
            $power = $artifact->power_level;

            foreach ($impactCoefficients as $key => $multiplier) {
                if (isset($culturalPressure[$key])) {
                    // Accumulate pressure from all active works
                    $culturalPressure[$key] += ($multiplier * $power);
                }
            }
        }

        // 3. Store the aggregated cultural pressure in the world state
        // This will be picked up by the CognitiveDynamicsEngine or ArchetypeClassifier in the next micro-cycle
        $state->set('meta.cultural_pressure', $culturalPressure);

        Log::debug("CULTURAL INFLUENCE: Applied pressure from " . $activeArtifacts->count() . " artifacts in Universe [{$universeId}]");

        return new EngineResult([
            'affected_artifacts' => $activeArtifacts->pluck('id')->toArray(),
            'pressure_vector' => $culturalPressure
        ], [], []);
    }
}

