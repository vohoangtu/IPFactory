<?php

declare(strict_types=1);

namespace App\Modules\Simulation\Services\Evaluation;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\Intelligence\Models\ActorEvent;
use App\Modules\Narrative\Models\Legend;
use App\Modules\Intelligence\Models\LegendaryAgent;
use App\Modules\Intelligence\Entities\ActorEntity;
use App\Modules\Simulation\Core\Contracts\WorldEventBusInterface;
use App\Modules\Simulation\Core\Engines\Meta\ActorDecisionEngine;
use App\Modules\Simulation\Core\Engines\Meta\ArtifactCreationEngine;
use App\Modules\Simulation\Core\Engines\Meta\CapabilityEngine;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Support\SimulationRandom;

class ActorDecisionOrchestrator
{
    public function __construct(
        protected CapabilityEngine $capabilityEngine,
        protected ActorDecisionEngine $actorDecisionEngine,
        protected ArtifactCreationEngine $artifactCreationEngine,
        protected WorldEventBusInterface $worldEventBus,
    ) {
    }

    /**
     * Phase 2: Run CapabilityEngine + ActorDecisionEngine for key actors; record action in actor_events.
     */
    public function runActorDecisionForUniverse($universe, $snapshot, SimulationRandom $rng): void
    {
        $maxActors = (int) \config('worldos.actor_decision.max_actors_per_pulse', 50);

        $keyActors = Actor::query()
            ->where('universe_id', $universe->id)
            ->where('is_alive', true)
            ->whereHas('supremeEntity')
            ->orderByDesc('id')
            ->limit($maxActors)
            ->get();

        $tick = (int) $snapshot->tick;
        $state = (array) ($snapshot->state_vector ?? []);
        $metrics = (array) ($snapshot->metrics ?? []);
        $environment = [
            'entropy' => $snapshot->entropy ?? 0.5,
            'stability_index' => $snapshot->stability_index ?? 0.5,
            'war_pressure' => $state['war_pressure'] ?? 0,
        ];

        foreach ($keyActors as $actor) {
            $this->capabilityEngine->computeAndStore($actor, $tick);
            $actor->refresh();
            $capabilities = $actor->capabilities ?? [];
            $traits = $actor->traits ?? array_fill(0, 17, 0.5);
            $birthTick = (int) ($actor->birth_tick ?? $tick);
            $belief = $this->getBeliefContextForActor($actor);
            $environment['belief'] = $belief;

            // DDD Mapping
            $actorEntity = new ActorEntity(
                id: $actor->id,
                universeId: (int) $actor->universe_id,
                name: $actor->name,
                archetype: $actor->archetype,
                traits: $traits,
                metrics: $actor->metrics ?? [],
                isAlive: (bool) $actor->is_alive,
                generation: (int) ($actor->generation ?? 1),
                biography: $actor->biography,
                isHeroic: (bool) $actor->is_heroic,
                heroicType: $actor->heroic_type,
                vocationId: $actor->vocation_id
            );

            $worldState = WorldState::fromArray($snapshot->state_vector ?? []);

            $dist = $this->actorDecisionEngine->getActionDistribution($actorEntity, $worldState, $tick);
            $action = $this->actorDecisionEngine->rollAction($dist, $rng);
            ActorEvent::create([
                'actor_id' => $actor->id,
                'tick' => $tick,
                'event_type' => $action,
                'context' => ['distribution' => $dist, 'rolled' => $action],
            ]);
            if ($this->actorDecisionEngine->isArtifactEligibleAction($action)) {
                $this->artifactCreationEngine->tryCreate($actor, $universe, $tick, $action, $rng);
            }
        }
    }

    /**
     * Build belief context for ActorDecisionEngine: religion, causal_trajectory belief, legend level.
     */
    public function getBeliefContextForActor(Actor $actor): array
    {
        $hasReligion = $actor->religions()->exists();
        $hasCausalTrajectoryBelief = $actor->causal_trajectoryBeliefs()->exists();
        $legendLevel = (int) $actor->legends()->max('legend_level');
        if ($legendLevel === 0 && $actor->supremeEntity) {
            $legendaryAgent = LegendaryAgent::where('original_agent_id', $actor->id)->first();
            if ($legendaryAgent) {
                $leg = Legend::where('legendary_agent_id', $legendaryAgent->id)->orderByDesc('legend_level')->first();
                $legendLevel = $leg ? (int) $leg->legend_level : 0;
            }
        }

        return [
            'has_religion' => $hasReligion,
            'has_causal_trajectory_belief' => $hasCausalTrajectoryBelief,
            'legend_level' => $legendLevel,
        ];
    }
}
