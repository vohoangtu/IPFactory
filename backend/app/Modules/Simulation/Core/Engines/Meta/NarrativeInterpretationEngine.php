<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Effects\WorldStateUpdateEffect;
use App\Modules\Narrative\Services\NarrativeCompiler;
use App\Modules\Narrative\Models\Narrative;
use Illuminate\Support\Facades\Log;

/**
 * NarrativeInterpretationEngine – Translates WorldEvents into Narratives.
 * 
 * Logic: Every high-impact event is interpreted by the "Blind Historian"
 * into a Narrative that has field effects (e.g. "God is punishing us" -> fear++, meaning++).
 */
class NarrativeInterpretationEngine implements SimulationEngine
{
    public function __construct(
        protected NarrativeCompiler $compiler
    ) {}

    public function name(): string { return 'NarrativeInterpretationEngine'; }
    public function version(): string { return '1.0.0'; }
    public function phase(): string { return 'POST_PHYSICS'; }
    public function priority(): int { return 100; }
    public function priorityCategory(): string { return 'STOCHASTIC'; }
    public function tickRate(): int { return 1; }
    public function isParallelSafe(): bool { return false; }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $effects = [];
        $events = [];
        
        // Note: The kernel doesn't pass the events of the CURRENT tick to handle() easily.
        // In a Living System, we might need to fetch events from the WorldEventBus or a temporary registry.
        // For now, we simulate by checking the tick manifest or recent high-impact events.
        
        $universeId = $ctx->getUniverseId();
        $tick = $ctx->getTick();
        
        // This is a placeholder for "Recent High-Impact Events" logic
        // In a production-grade system, this engine would run AFTER all other engines
        // and collect their emitted events.
        
        $stability = (float) $state->get('metrics.stability_index', 0.5);
        $entropy = (float) $state->getEntropy();
        
        if ($entropy > 0.7 || $stability < 0.3) {
            $story = $this->compiler->compile([
                'entropy' => $entropy,
                'stability_index' => $stability,
                'historical_block' => [
                    'tick' => $tick,
                    'events' => ['Instability Detected']
                ]
            ], $entropy * 0.5);

            $narrative = Narrative::create([
                'universe_id' => $universeId,
                'tick_born' => $tick,
                'story' => $story,
                'virality' => 0.1,
                'distortion' => $entropy * 0.2,
                'field_effects' => [
                    'fear' => 0.05,
                    'meaning' => 0.1
                ],
                'tags' => ['instability', 'omen'],
                'is_active' => true
            ]);

            Log::info("NarrativeInterpretationEngine: New narrative born", ['story' => $story]);
            
            // Immediately apply effects to the global state
            $effects[] = new WorldStateUpdateEffect($narrative->field_effects);
        }

        return new EngineResult($events, $effects);
    }
}

