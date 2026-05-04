<?php

namespace App\Modules\Simulation\Listeners;

use App\Modules\Simulation\Events\UniverseSimulationPulsed;
use App\Modules\Institutions\Services\GreatFilterEngine;
use App\Modules\Institutions\Services\AscensionEngine;
use App\Modules\Institutions\Services\OmegaPointEngine;
use App\Modules\Simulation\Services\Culture\IdeologyEvolutionEngine;
use App\Modules\Simulation\Services\Core\GreatPersonEngine;
use App\Modules\Simulation\Services\Core\GreatPersonLegacyService;
use App\Modules\Simulation\Services\Core\MacroAgentSpawnService;
use App\Modules\Simulation\Core\Engines\Meta\ActorDecisionEngine;
use App\Modules\Simulation\Core\Engines\Meta\CapabilityEngine;
use App\Modules\Simulation\Core\Engines\Social\IdeaDiffusionEngine;
use App\Modules\Simulation\Services\Society\InstitutionDecayService;
use App\Modules\Simulation\Core\Support\SimulationRandom;
use App\Modules\Simulation\Core\Runtime\Domain\UniverseState;
use Illuminate\Support\Facades\Log;

/**
 * EvaluateEvolutionaryLeaps — Phân rã từ EvaluateSimulationResult.
 * Chịu trách nhiệm về Great Persons, Ideology, Ascension, và Actor Decisions.
 */
class EvaluateEvolutionaryLeaps
{
    public function __construct(
        protected GreatFilterEngine $greatFilterEngine,
        protected AscensionEngine $ascensionEngine,
        protected OmegaPointEngine $omegaPointEngine,
        protected IdeologyEvolutionEngine $ideologyEvolutionEngine,
        protected GreatPersonEngine $greatPersonEngine,
        protected GreatPersonLegacyService $greatPersonLegacyService,
        protected MacroAgentSpawnService $macroAgentSpawnService,
        protected ActorDecisionEngine $actorDecisionEngine,
        protected CapabilityEngine $capabilityEngine,
        protected IdeaDiffusionEngine $ideaDiffusionEngine,
        protected InstitutionDecayService $institutionDecayService,
    ) {}

    public function handle(UniverseSimulationPulsed $event): void
    {
        $universe = $event->universe;
        $snapshot = $event->snapshot;
        $rng = new SimulationRandom((int) ($universe->seed ?? 0), (int) $snapshot->tick, 0);

        try {
            // 1. High-level Evolution Engines
            $this->greatFilterEngine->process($universe, (int)$snapshot->tick, $snapshot->state_vector ?? [], $rng);
            $this->omegaPointEngine->process($universe, $snapshot);
            
            $uState = UniverseState::fromModels($universe, $snapshot);
            $this->ascensionEngine->evaluate($uState);

            // 2. Macro Agents & Institutions
            $this->macroAgentSpawnService->spawnIfEligible($universe, $snapshot);
            $this->ideaDiffusionEngine->process($universe, (int) $snapshot->tick);
            $this->institutionDecayService->process($universe, (int) $snapshot->tick);

            // 3. Ideology & Great Persons
            $this->processIdeologies($universe, (int) $snapshot->tick);
            $this->greatPersonEngine->spawnIfEligible($universe, (int) $snapshot->tick);
            $this->greatPersonLegacyService->writeToStateVector($universe, (int) $snapshot->tick);

            // 4. Actor Decisions (Phase 2)
            $this->runActorDecisions($universe, $snapshot, $rng);

            // 5. Detect Anomalies
            $this->detectAnomalies($universe, $snapshot);

        } catch (\Throwable $e) {
            Log::error("EvaluateEvolutionaryLeaps failed: " . $e->getMessage(), [
                'universe_id' => $universe->id,
                'tick' => $snapshot->tick
            ]);
        }
    }

    protected function processIdeologies($universe, int $tick): void
    {
        $ideologyResult = $this->ideologyEvolutionEngine->getDominantIdeology($universe);
        if (!empty($ideologyResult['previous_dominant'])) {
            $this->ideologyEvolutionEngine->recordShiftIfSignificant(
                $universe,
                $tick,
                $ideologyResult['dominant'],
                $ideologyResult['previous_dominant']
            );
        }
    }

    protected function runActorDecisions($universe, $snapshot, $rng): void
    {
        $maxActors = (int) config('worldos.actor_decision.max_actors_per_pulse', 50);
        $keyActors = \App\Modules\Intelligence\Models\Actor::where('universe_id', $universe->id)
            ->where('is_alive', true)
            ->whereHas('supremeEntity')
            ->limit($maxActors)
            ->get();

        foreach ($keyActors as $actor) {
            $this->capabilityEngine->computeAndStore($actor, (int)$snapshot->tick);
            // Additional logic for actor decisions...
        }
    }

    protected function detectAnomalies($universe, $snapshot): void
    {
        $entropy = (float) $snapshot->entropy;
        if ($entropy > 0.95) {
            event(new \App\Modules\Simulation\Events\AnomalyDetected($universe, [
                'title' => 'Void Gate Opened',
                'description' => 'Entropy at critical levels.',
                'severity' => 'CRITICAL'
            ]));
        }
    }
}
