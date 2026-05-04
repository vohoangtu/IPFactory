<?php

declare(strict_types=1);

namespace App\Modules\Intelligence\Actions;

use App\Modules\Intelligence\Domain\Rng\SimulationRng;
use App\Modules\Intelligence\Domain\Entropy\EntropyBudget;
use App\Modules\Intelligence\Domain\Society\SocialFieldCalculator;
use App\Modules\Intelligence\Domain\Phase\PhaseDetector;
use App\Modules\Intelligence\Services\CognitiveDynamicsEngine;
use App\Modules\Intelligence\Services\ActorTransitionSystem;
use App\Modules\Intelligence\Services\ReplicatorDistributionUpdater;
use App\Modules\Intelligence\Services\MacroStateEvolution;
use App\Modules\Intelligence\Services\SocietyAnalyzer;
use App\Modules\Intelligence\Services\PhaseMetricsComputer;
use App\Modules\Intelligence\Services\DeterminismHasher;
use App\Modules\Simulation\Services\Ecology\SimulationPRNG;
use App\Modules\World\Models\Universe;

class RunMicroCycleAction implements \App\Contracts\ActionInterface
{
    public function __construct(
        private SocialFieldCalculator $socialFieldCalculator,
        private CognitiveDynamicsEngine $cognitiveDynamicsEngine,
        private ActorTransitionSystem $transitionSystem,
        private UpdateArchetypeAction $updateArchetypeAction,
        private ReplicatorDistributionUpdater $replicatorUpdater,
        private PhaseMetricsComputer $phaseMetricsComputer,
        private MacroStateEvolution $macroEvolution,
        private SocietyAnalyzer $societyAnalyzer,
        private DeterminismHasher $determinismHasher,
        private \App\Modules\Simulation\Core\Engines\Meta\CulturalInfluenceEngine $culturalInfluenceEngine
    ) {}

    public function handle(Universe $universe, int $tick, array $actorStates, array $worldAxiom): array
    {
        $globalEntropy = $universe->entropy ?? 0.5;
        $seed = $universe->seed ?? 0;

        $budget = new EntropyBudget($globalEntropy, count($actorStates));

        // Phase 13: Cultural Influence
        $worldState = new \App\Modules\Simulation\Core\Runtime\State\WorldState($universe->state_vector ?? []);
        $ctx = new \App\Modules\Simulation\Core\Domain\TickContext($universe->id, $tick, $seed);
        $this->culturalInfluenceEngine->handle($worldState, $ctx);
        $universe->state_vector = $worldState->toArray();

        // 1. Calculate Social Field
        $socialField = $this->socialFieldCalculator->calculate($actorStates);

        // Compute Ratios up front
        $ratios = $this->replicatorUpdater->computeRatios($actorStates);

        // Compute Phase early for landscape multipliers
        $metricsResult = $this->phaseMetricsComputer->compute($universe, $actorStates, $ratios, $tick);
        $phaseScore = $metricsResult['phase'];

        // Iterate over actors applying pure transitions
        $nextActorStates = [];
        foreach ($actorStates as $actor) {
            $rng = new SimulationRng($seed, $tick, $actor->id ?? 0);

            // Step 1: Cognitive Dynamics
            $actor = $this->cognitiveDynamicsEngine->update($actor, $socialField, $rng, $budget);

            // Step 2: Survival check
            $fitness = (float) ($universe->state_vector['survival_modifier'] ?? 1.0);
            $actor = $this->transitionSystem->processSurvival($actor, $globalEntropy, $rng, 0.0, $fitness);

            // Step 3: Drift & Update Archetype
            $culturalPressure = $universe->state_vector['cultural_pressure'] ?? [];
            $actor = $this->updateArchetypeAction->handle(
                $actor,
                $worldAxiom,
                $globalEntropy,
                $ratios,
                $phaseScore,
                [],
                $culturalPressure
            );

            $nextActorStates[] = $actor;
        }

        // Step 4: Recompute new ratios post-drift
        $newRatios = $this->replicatorUpdater->computeRatios($nextActorStates);

        // Step 5: Factions Detection
        $fragmentedScore = $phaseScore->fragmented;
        $microRng = new SimulationPRNG($seed + $tick);
        $factionsToSpawn = $this->societyAnalyzer->detectEmergentFactions($universe, $newRatios, $fragmentedScore, $microRng);
        $this->societyAnalyzer->storeFactions($universe, $factionsToSpawn, $tick, $microRng);

        // Step 6: Macro State Evolution
        $macroRng = new SimulationRng($seed, $tick, 999999);
        $rngNoise = ($macroRng->nextFloat() * 2 - 1);

        $universe = $this->macroEvolution->evolve(
            $universe,
            $newRatios,
            $metricsResult['polarization'],
            $rngNoise
        );

        // Step 7: Verify Determinism Hash
        $hash = $this->determinismHasher->hash($nextActorStates, $universe);

        return [
            'universe' => $universe,
            'actors' => $nextActorStates,
            'metrics' => [
                'entropy' => $universe->entropy,
                'polarization_index' => $metricsResult['polarization'],
                'social_cohesion' => $metricsResult['cohesion'],
                'cultural_momentum' => $metricsResult['momentum'],
                'phase_score' => $phaseScore->toArray(),
                'archetype_distribution' => $newRatios,
                'snapshot_hash' => $hash,
            ],
        ];
    }
}
