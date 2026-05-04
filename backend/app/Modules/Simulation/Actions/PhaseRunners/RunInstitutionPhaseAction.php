<?php
namespace App\Modules\Simulation\Actions\PhaseRunners;

use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\Simulation\Core\Support\SimulationRandom;
use App\Modules\Simulation\Services\Core\AdaptiveSchedulerService;
use App\Modules\Institutions\Services\ZoneConflictEngine;
use App\Modules\Institutions\Services\WorldEdictEngine;
use App\Modules\Institutions\Services\GreatFilterEngine;
use App\Modules\Institutions\Services\AscensionEngine;
use App\Modules\Institutions\Services\OmegaPointEngine;
use App\Modules\Simulation\Services\Culture\IdeologyEvolutionEngine;
use App\Modules\Simulation\Services\Core\GreatPersonEngine;
use App\Modules\Simulation\Services\Core\GreatPersonLegacyService;
use App\Modules\Simulation\Services\Core\MacroAgentSpawnService;
use App\Modules\Simulation\Core\Engines\Meta\CapabilityEngine;
use App\Modules\Simulation\Core\Engines\Meta\ActorDecisionEngine;
use App\Modules\Simulation\Core\Engines\Meta\ArtifactCreationEngine;
use App\Modules\Simulation\Core\Engines\Social\IdeaDiffusionEngine;
use App\Modules\Simulation\Services\Society\InstitutionDecayService;
use App\Modules\Simulation\Services\Core\HeroLifecycleService;
use App\Modules\Simulation\Core\Runtime\Domain\UniverseState;
use App\Modules\Intelligence\Entities\ActorEntity;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class RunInstitutionPhaseAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected AdaptiveSchedulerService $adaptiveScheduler,
        protected ZoneConflictEngine $zoneConflictEngine,
        protected WorldEdictEngine $worldEdictEngine,
        protected GreatFilterEngine $greatFilterEngine,
        protected AscensionEngine $ascensionEngine,
        protected OmegaPointEngine $omegaPointEngine,
        protected IdeologyEvolutionEngine $ideologyEvolutionEngine,
        protected GreatPersonEngine $greatPersonEngine,
        protected GreatPersonLegacyService $greatPersonLegacyService,
        protected MacroAgentSpawnService $macroAgentSpawnService,
        protected CapabilityEngine $capabilityEngine,
        protected ActorDecisionEngine $actorDecisionEngine,
        protected ArtifactCreationEngine $artifactCreationEngine,
        protected IdeaDiffusionEngine $ideaDiffusionEngine,
        protected InstitutionDecayService $institutionDecayService,
        protected HeroLifecycleService $heroLifecycleService
    ) {}

    public function execute(Universe $universe, UniverseSnapshot $snapshot, SimulationRandom $rng): void
    {
        // 1. Emerging Civilizations
        if ($this->adaptiveScheduler->shouldRun('zone_conflict', $universe, $snapshot)) {
            $this->zoneConflictEngine->resolveConflicts($universe, $snapshot, $rng);
        }

        // 2. Deep Sim Phase B: spawn macro agents (ruler/army)
        $universe->refresh();
        try {
            $this->macroAgentSpawnService->spawnIfEligible($universe, $snapshot);
        } catch (\Throwable $e) {
            Log::warning('MacroAgentSpawnService spawn failed: ' . $e->getMessage());
        }

        // 3. Actor Decision (Phase 2)
        if (\config('worldos.pulse.run_actor_decision', false)) {
            if ($this->adaptiveScheduler->shouldRun('actor_decision', $universe, $snapshot)) {
                try {
                    $this->runActorDecisionForUniverse($universe, $snapshot, $rng);
                } catch (\Throwable $e) {
                    Log::warning('Actor decision failed: ' . $e->getMessage());
                }
            }
        }

        // 4. Social & Society Dynamics
        if (\config('worldos.idea_diffusion.run_on_pulse', false)) {
            if ($this->adaptiveScheduler->shouldRun('idea_diffusion', $universe, $snapshot)) {
                try {
                    $this->ideaDiffusionEngine->process($universe, (int) $snapshot->tick);
                } catch (\Throwable $e) {
                    Log::warning('Idea diffusion failed: ' . $e->getMessage());
                }
            }
        }
        if (\config('worldos.institution.run_decay_on_pulse', false)) {
            if ($this->adaptiveScheduler->shouldRun('institution_decay', $universe, $snapshot)) {
                try {
                    $this->institutionDecayService->process($universe, (int) $snapshot->tick);
                } catch (\Throwable $e) {
                    Log::warning('Institution decay failed: ' . $e->getMessage());
                }
            }
        }

        // 5. Governance
        $this->worldEdictEngine->decree($universe, $snapshot);

        // 6. Great Filter & Zenith Points
        $this->greatFilterEngine->process($universe, (int)$snapshot->tick, $snapshot->state_vector ?? [], $rng);
        
        $uState = UniverseState::fromModels($universe, $snapshot);
        $this->ascensionEngine->evaluate($uState);
        $this->omegaPointEngine->process($universe, $snapshot);

        // 7. Ideologies & Great Persons
        if (\config('worldos.pulse.run_ideology', true)) {
            if ($this->adaptiveScheduler->shouldRun('ideology_evolution', $universe, $snapshot)) {
                try {
                    $ideologyResult = $this->ideologyEvolutionEngine->getDominantIdeology($universe);
                    if (! empty($ideologyResult['previous_dominant'])) {
                        $this->ideologyEvolutionEngine->recordShiftIfSignificant(
                            $universe,
                            (int) $snapshot->tick,
                            $ideologyResult['dominant'],
                            $ideologyResult['previous_dominant']
                        );
                    }
                } catch (\Throwable $e) {
                    Log::warning('Pulse: Ideology evolution failed: ' . $e->getMessage());
                }
            }
        }
        if (\config('worldos.pulse.run_great_person', true)) {
            if ($this->adaptiveScheduler->shouldRun('great_person', $universe, $snapshot)) {
                try {
                    $this->greatPersonEngine->spawnIfEligible($universe, (int) $snapshot->tick);
                } catch (\Throwable $e) {
                    Log::warning('Pulse: Great Person spawn failed: ' . $e->getMessage());
                }
            }
        }
        if (\config('worldos.pulse.run_great_person_legacy', true)) {
            try {
                $this->greatPersonLegacyService->writeToStateVector($universe, (int) $snapshot->tick);
            } catch (\Throwable $e) {
                Log::warning('Pulse: Great Person legacy aggregate failed: ' . $e->getMessage());
            }
        }
        
        // 8. Hero Lifecycle
        try {
            $this->heroLifecycleService->process($universe, (int) $snapshot->tick);
        } catch (\Throwable $e) {
            Log::warning('Pulse: Hero lifecycle process failed: ' . $e->getMessage());
        }
    }

    protected function getBeliefContextForActor(\App\Modules\Intelligence\Models\Actor $actor): array
    {
        $hasReligion = $actor->religions()->exists();
        $hasCausalTrajectoryBelief = $actor->causal_trajectoryBeliefs()->exists();
        $legendLevel = (int) $actor->legends()->max('legend_level');
        if ($legendLevel === 0 && $actor->supremeEntity) {
            $legendaryAgent = \App\Modules\Intelligence\Models\LegendaryAgent::where('original_agent_id', $actor->id)->first();
            if ($legendaryAgent) {
                $leg = \App\Modules\Narrative\Models\Legend::where('legendary_agent_id', $legendaryAgent->id)->orderByDesc('legend_level')->first();
                $legendLevel = $leg ? (int) $leg->legend_level : 0;
            }
        }
        return [
            'has_religion' => $hasReligion,
            'has_causal_trajectory_belief' => $hasCausalTrajectoryBelief,
            'legend_level' => $legendLevel,
        ];
    }

    protected function runActorDecisionForUniverse($universe, $snapshot, SimulationRandom $rng): void
    {
        $maxActors = (int) Config::get('worldos.actor_decision.max_actors_per_pulse', 50);

        $keyActors = \App\Modules\Intelligence\Models\Actor::query()
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

            // DDD Mapping: Map Actor Model to ActorEntity and Snapshot state to WorldState
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
            \App\Modules\Intelligence\Models\ActorEvent::create([
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
}
