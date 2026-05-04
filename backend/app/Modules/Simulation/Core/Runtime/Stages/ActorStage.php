<?php

namespace App\Modules\Simulation\Core\Runtime\Stages;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Runtime\Contracts\SimulationStageInterface;
use App\Modules\Intelligence\Actions\ProcessActorEnergyAction;
use App\Modules\Intelligence\Actions\ProcessActorSurvivalAction;
use App\Modules\Intelligence\Services\ActorBehaviorEngine;
use App\Modules\Intelligence\Services\LanguageEngine;

/**
 * Actor simulation stage: energy, survival, behavior, language.
 * Culture runs in CultureStage.
 */
final class ActorStage implements SimulationStageInterface
{
    public function __construct(
        protected ProcessActorEnergyAction $processActorEnergy,
        protected ProcessActorSurvivalAction $processActorSurvival,
        protected ActorBehaviorEngine $actorBehaviorEngine,
        protected LanguageEngine $languageEngine,
        protected \App\Modules\Intelligence\Services\Consciousness\CollectiveConsciousnessEngine $consciousnessEngine,
        protected \App\Modules\Intelligence\Domain\Society\SocialFieldCalculator $socialFieldCalculator,
        protected \App\Modules\Intelligence\Services\CognitiveDynamicsEngine $cognitiveDynamicsEngine,
        protected \App\Modules\Intelligence\Contracts\ActorRepositoryInterface $actorRepository,
        protected \App\Modules\Simulation\Core\Runtime\State\StateManager $stateManager
    ) {}

    public function run(Universe $universe, int $tick, ?UniverseSnapshot $savedSnapshot = null, array $context = []): void
    {
        $state = $this->stateManager->get();
        if (!$state) {
            return;
        }

        $response = array_merge($context, ['_ticks' => $context['_ticks'] ?? 1]);
        
        // 1. Physical/Survival Needs
        $this->processActorEnergy->runWithState($state, $response);
        $this->processActorSurvival->runWithState($state, $response);

        \Illuminate\Support\Facades\Log::debug("ActorStage: Processed survival for " . count($state->getActorEntities()) . " actors");

        // 2. Cognitive Dynamics (Phase 21)
        $actors = $state->getActorEntities();
        $aliveActors = array_filter($actors, fn($a) => $a->isAlive);
        $fields = $state->getFields();
        $field = $this->socialFieldCalculator->calculate($aliveActors); 
        
        $budget = new \App\Modules\Intelligence\Domain\Entropy\EntropyBudget($universe->entropy ?? 0.5, count($aliveActors));
        $universeSeed = (int)($universe->seed ?? 0);

        foreach ($aliveActors as $actor) {
            $rng = new \App\Modules\Intelligence\Domain\Rng\SimulationRng($universeSeed, $tick, $actor->id ?? 0);
            
            $actorState = $actor->toState();
            $updatedState = $this->cognitiveDynamicsEngine->update($actorState, $field, $rng, $budget);
            $actor->fromState($updatedState);
            // No individual save here, handled by StateManager
        }

        // 3. Behavior & Language
        $this->actorBehaviorEngine->runWithState($state, $tick);
        $this->languageEngine->runWithState($state, $tick);

        // 4. Collective Consciousness (Phase 43)
        $this->consciousnessEngine->runWithState($state, $tick);
    }
}


