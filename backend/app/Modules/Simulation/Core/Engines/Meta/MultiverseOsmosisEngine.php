<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseInteraction;
use App\Modules\Simulation\Core\Concerns\DefaultSimulationEnginePhase;
use App\Modules\Simulation\Core\Contracts\SimulationEngine;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Effects\OsmosisUpdateEffect;
use App\Modules\Simulation\Core\Events\WorldEvent;
use App\Modules\Simulation\Core\Events\WorldEventType;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use Illuminate\Support\Facades\Log;
use function resource_path;
use function file_get_contents;
use function max;

/**
 * Multiverse Osmosis Engine: simulates "Reality Bleeding" between high-resonance universes.
 * High-resonance partners leak innovation, spirituality, myth, or entropy to each other.
 */
final class MultiverseOsmosisEngine implements SimulationEngine
{
    use DefaultSimulationEnginePhase;

    public function phase(): string
    {
        return 'meta';
    }

    public function __construct(
        private ?RuleVmService $ruleVm = null
    ) {
    }

    public function name(): string
    {
        return 'multiverse_osmosis';
    }

    public function priority(): int
    {
        return 5; // Runs after cosmic pressure
    }

    public function tickRate(): int
    {
        return max(1, (int) (\config('worldos.time_scale_factors.multiverse_osmosis', 1)));
    }

    public function handle(WorldState $state, TickContext $ctx): EngineResult
    {
        $universeId = (int) $ctx->getUniverseId();
        
        // 1. Find resonance partners
        $interactions = UniverseInteraction::where('interaction_type', 'resonance')
            ->where(function($q) use ($universeId) {
                $q->where('universe_a_id', $universeId)
                  ->orWhere('universe_b_id', $universeId);
            })
            ->where('created_at', '>=', now()->subHours(24))
            ->get();

        if ($interactions->isEmpty()) {
            return new EngineResult([], [], []);
        }

        $allEffects = [];
        $events = [];

        foreach ($interactions as $interaction) {
            $partnerId = ($interaction->universe_a_id === $universeId) 
                ? $interaction->universe_b_id 
                : $interaction->universe_a_id;
            
            $partner = Universe::find($partnerId);
            if (!$partner) continue;

            $resonance = (float) ($interaction->payload['strength'] ?? $interaction->resonance_level ?? 0.5);
            
            $effect = $this->evaluateOsmosis($state, $partner, $resonance, $ctx);
            if ($effect) {
                $allEffects[] = $effect;
                
                $events[] = WorldEvent::create(
                    'multiverse_osmosis_bleed',
                    $universeId,
                    $ctx->getTick(),
                    null,
                    [],
                    0.3,
                    [],
                    [
                        'source_universe_id' => $partnerId,
                        'resonance' => $resonance,
                    ]
                );
            }
        }

        return new EngineResult($events, $allEffects, []);
    }

    private function evaluateOsmosis(WorldState $targetState, Universe $source, float $resonance, TickContext $ctx): ?OsmosisUpdateEffect
    {
        // DSL state preparation
        $targetVec = $targetState->getStateVector();
        $sourceVec = $source->state_vector ?? [];

        $vmState = [
            'resonance' => $resonance,
            'source' => [
                'innovation' => (float) ($sourceVec['innovation_metrics']['total_score'] ?? $source->entropy ?? 0.5),
                'spirituality' => (float) ($sourceVec['fields']['belief_field'] ?? 0.0),
                'myth' => (float) ($sourceVec['fields']['belief_field'] * 0.5), // Approximate myth from belief
                'entropy' => (float) ($source->entropy ?? 0.5),
            ],
            'target' => [
                'innovation' => (float) ($targetVec['innovation_metrics']['total_score'] ?? $targetState->getEntropy()),
                'spirituality' => (float) ($targetVec['fields']['belief_field'] ?? 0.0),
                'myth' => (float) ($targetVec['fields']['belief_field'] * 0.5),
                'entropy' => (float) ($targetState->getEntropy()),
            ],
            'bleed' => [
                'innovation_gain' => 0.0,
                'spirituality_gain' => 0.0,
                'myth_gain' => 0.0,
                'entropy_gain' => 0.0,
            ]
        ];

        $dslFile = resource_path('worldos_rules/multiverse/osmosis.dsl');
        $dsl = @file_get_contents($dslFile) ?: '';
        
        $result = $this->ruleVm->evaluateRawState($vmState, $dsl);

        if (!($result['ok'] ?? false)) {
            return null;
        }

        $bleed = $result['state']['bleed'] ?? [];
        if (empty(array_filter($bleed))) {
            return null;
        }

        return new OsmosisUpdateEffect($bleed);
    }
}




