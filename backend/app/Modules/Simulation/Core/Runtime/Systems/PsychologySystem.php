<?php

namespace App\Modules\Simulation\Core\Runtime\Systems;

use App\Modules\Simulation\Core\Runtime\Contracts\WorldSystemInterface;
use App\Modules\Simulation\Core\Runtime\Causality\ImpactReport;
use App\Modules\Simulation\Core\Runtime\State\StateManager;
use App\Modules\Psychology\Services\MeaningEngine;
use App\Modules\Psychology\ValueObjects\PsychologicalState;
use App\Modules\Psychology\ValueObjects\TraitVector;
use App\Modules\Psychology\ValueObjects\MemoryStream;
use App\Modules\Psychology\ValueObjects\MemoryItem;
use Illuminate\Support\Facades\Log;
use function data_get;
use function app;

/**
 * PsychologySystem – The bridge between World Events (Scars) and Actor Cognition (§V10).
 * Runs in PHASE_MIND.
 */
class PsychologySystem implements WorldSystemInterface
{
    public function __construct(
        protected MeaningEngine $meaningEngine,
        protected StateManager $stateManager
    ) {}

    public function update(array $context, int $tick): ?ImpactReport
    {
        $state = $this->stateManager->get();
        if (!$state) return null;

        $scars = $state->getScars();
        if (empty($scars)) {
             // Still need to decay emotions even if no events happened
             return $this->processEmotionalDecay($state, $tick);
        }

        $actors = $state->getActorEntities();
        if (empty($actors)) return null;

        $report = new ImpactReport(
            'psychology_interpretation',
            \App\Modules\Simulation\Core\Runtime\WorldKernel::PHASE_MIND,
            \App\Modules\Simulation\Core\Runtime\WorldKernel::RULE_OBSERVATION,
            'Interpreted event meanings for active agents.'
        );

        foreach ($actors as $actor) {
            // 1. Filter scars relevant to this actor (same zone or explicitly targeting)
            $relevantScars = array_filter($scars, function($scar) use ($actor) {
                // If the scar has a zone_id, it must match the actor's zone
                if (isset($scar['zone_id']) && $scar['zone_id'] != $actor->zone_id) {
                    return false;
                }
                return true; // Simplified: all scars in zone or global scars
            });

            if (empty($relevantScars)) {
                $this->decayActorEmotions($actor);
                continue;
            }

            // 2. Prepare Psychology context
            $traitVector = TraitVector::fromArray(['traits' => data_get($actor->metrics, 'trait_vector', [])]);
            $psychState = PsychologicalState::fromArray(data_get($actor->metrics, 'psych_state', []));
            $memory = MemoryStream::fromArray(data_get($actor->metrics, 'memory_stream', []), $tick);
            
            // Social context dummy (will be improved in Phase 8)
            $socialContext = ['liking' => 0.0, 'fear_of_source' => 0.0];

            foreach ($relevantScars as $scar) {
                $eventType = $scar['category'] ?? 'default';
                
                // 3. Interpret meaning
                $meaning = $this->meaningEngine->interpret($eventType, $traitVector, $psychState, $memory, $socialContext);
                
                // 4. Update psychological state based on meaning
                $psychState->applyDelta([
                    'fear'    => ($meaning->valence < 0 ? abs($meaning->valence) : 0) * $meaning->intensity * 0.5,
                    'anger'   => ($meaning->type === 'conflict' ? 0.3 : 0) * $meaning->intensity,
                    'sadness' => ($meaning->type === 'loss' ? 0.4 : 0) * $meaning->intensity,
                    'joy'     => ($meaning->valence > 0 ? $meaning->valence : 0) * $meaning->intensity,
                    'stress'  => $meaning->intensity * 0.2,
                ]);

                // 5. Push to memory
                $memory->push(MemoryItem::fromEvent(
                    type:     $meaning->type,
                    valence:  $meaning->valence,
                    intensity:$meaning->intensity,
                    tick:     $tick
                ));

                $report->log('actor', $actor->id, 'interpreted_event', $eventType, $meaning->valence);
            }

            // 6. Decay and Save back to metrics
            $psychState->decay();
            $memory->decayAll();

            $metrics = $actor->metrics;
            $metrics['psych_state'] = $psychState->toArray();
            $metrics['memory_stream'] = $memory->toArray();
            $actor->metrics = $metrics;

            // Note: We don't save model here, WorldKernel or ISTE handles persistence
        }

        return $report;
    }

    private function processEmotionalDecay($state, int $tick): ?ImpactReport
    {
        $actors = $state->getActorEntities();
        foreach ($actors as $actor) {
            $this->decayActorEmotions($actor);
        }
        return null; 
    }

    private function decayActorEmotions($actor): void
    {
        $psychData = data_get($actor->metrics, 'psych_state', []);
        if (empty($psychData)) return;

        $psychState = PsychologicalState::fromArray($psychData);
        $psychState->decay();
        
        $metrics = $actor->metrics;
        $metrics['psych_state'] = $psychState->toArray();
        $actor->metrics = $metrics;
    }
}
