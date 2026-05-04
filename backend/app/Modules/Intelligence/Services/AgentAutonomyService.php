<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\Intelligence\Models\AgentDecision;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\World\Models\Universe;
use App\Modules\Simulation\Models\UniverseDecisionModel;
use App\Modules\Intelligence\Actions\FormContractAction;
use App\Modules\Intelligence\Actions\MigrateAction;
use App\Modules\Intelligence\Actions\PropagateMythAction;
use App\Modules\Intelligence\Actions\RevoltAction;
use App\Modules\Intelligence\Contracts\ActorRepositoryInterface;
use App\Modules\Intelligence\Contracts\AgentActionInterface;
use App\Modules\Intelligence\Domain\Policy\ActionResult;
use App\Modules\Intelligence\Domain\Policy\DecisionModel;
use App\Modules\Intelligence\Domain\Policy\ModelType;
use App\Modules\Intelligence\Domain\Policy\UniverseContext;
use App\Modules\Intelligence\Entities\ActorEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Refactored AgentAutonomyService — Phase 7.
 *
 * Responsibilities stripped to orchestration only:
 *   1. Resolve actors
 *   2. Build UniverseContext
 *   3. Evaluate via DecisionEngine + ActionSelector
 *   4. Execute via Strategy Action
 *   5. Persist side-effects (biography, chronicle, universe impact, agent decision)
 *
 * All business logic lives in DecisionEngine, DecisionModel, and Action classes.
 */
class AgentAutonomyService
{
    /** @var array<string, AgentActionInterface> */
    private array $actions;

    public function __construct(
        private readonly ActorRepositoryInterface $actorRepository,
        private readonly DecisionEngine           $decisionEngine,
        private readonly ActionSelector           $actionSelector,
        private readonly LoomIntentClient         $loomClient,
        private readonly IntentActionMapper       $intentMapper,
    ) {
        // Register Strategy Actions
        $this->actions = [
            'revolt'          => new RevoltAction(),
            'migrate'         => new MigrateAction(),
            'propagate_myth'  => new PropagateMythAction(),
            'form_contract'   => new FormContractAction($this->actorRepository),
        ];
    }

    /**
     * Standard (non-arena) simulation tick — uses greedy selection, no decision model.
     */
    public function process(Universe $universe, int $tick): void
    {
        $this->processWithModel($universe, $tick, null);
    }

    /**
     * Arena tick: accepts an optional DecisionModel.
     * Falls back to default linear model if none provided.
     */
    public function processWithModel(Universe $universe, int $tick, ?DecisionModel $model): void
    {
        $actors  = $this->actorRepository->findByUniverse($universe->id);
        $ctx     = UniverseContext::fromStateVector($universe->state_vector ?? [], $tick);

        // Build a minimal default model when running outside the arena
        if ($model === null) {
            $model = $this->loadOrBuildDefaultModel($universe);
        }

        foreach ($actors as $actor) {
            if (!$actor->isAlive) {
                continue;
            }

            // ── LLM Intent Layer (sampling) ────────────────────────────────
            if ($this->shouldCallLoom($actor, $ctx, $tick)) {
                \Log::info("LoomIntent triggering for actor {$actor->name} (ID: {$actor->id}) at tick {$tick}");
                $intent = $this->loomClient->requestIntent($actor, $ctx);
                if ($intent !== null) {
                    \Log::info("LoomIntent received action: {$intent->action} for {$actor->name}");
                    $result = $this->intentMapper->execute($intent, $actor, $universe, $ctx, $tick);
                    if ($result !== null) {
                        DB::transaction(function () use ($actor, $universe, $tick, $intent, $result) {
                            $this->applyResult(
                                $actor, $universe, $tick, $intent->action, $intent->confidence, $result,
                                $intent->reasoning, $intent->confidence
                            );
                        });
                        $universe->refresh();
                        $ctx = UniverseContext::fromStateVector($universe->state_vector ?? [], $tick);
                        continue; // Skip DecisionEngine for this actor this tick
                    }
                }
            }

            // ── DecisionEngine fallback ────────────────────────────────────
            $scores      = $this->decisionEngine->evaluate($actor, $ctx, $model);
            $selectedAct = $this->actionSelector->greedy($scores, $model->thresholdVector);

            if ($selectedAct === null) {
                continue;
            }

            $action = $this->actions[$selectedAct] ?? null;
            if ($action === null) {
                continue;
            }

            $result = $action->execute($actor, $universe, $ctx, $tick);

            DB::transaction(function () use ($actor, $universe, $tick, $selectedAct, $scores, $result) {
                $this->applyResult($actor, $universe, $tick, $selectedAct, $scores[$selectedAct], $result);
            });

            // Refresh context after each actor so the universe state is current
            $universe->refresh();
            $ctx = UniverseContext::fromStateVector($universe->state_vector ?? [], $tick);
        }
    }

    // ── Loom sampling ─────────────────────────────────────────────────────────

    /**
     * Decide whether to call narrative-loom for this actor this tick.
     * Sampling rules to avoid LLM latency on every actor every tick.
     */
    private function shouldCallLoom(ActorEntity $actor, UniverseContext $ctx, int $tick): bool
    {
        // Rule 1: High-dominance or high-ambition actors get LLM attention frequently
        $dominance = $actor->traits[0] ?? 0; // index 0 = Dominance
        $ambition  = $actor->traits[1] ?? 0; // index 1 = Ambition
        if (($dominance > 0.8 || $ambition > 0.8) && $tick % 20 === 0) {
            return true;
        }

        // Rule 2: Crisis (high entropy) → LLM for influential actors
        if ($ctx->entropy > 0.7 && ($dominance > 0.6 || $ambition > 0.6) && $tick % 10 === 0) {
            return true;
        }

        // Rule 3: Every 50 ticks, randomly pick 1 actor for exploration
        if ($tick % 50 === 0 && mt_rand(0, 9) === 0) {
            return true;
        }

        return false;
    }

    // ── Apply ActionResult ──────────────────────────────────────────────────

    private function applyResult(
        ActorEntity $actor,
        Universe    $universe,
        int         $tick,
        string      $actionType,
        float       $score,
        ActionResult $result,
        ?string     $reasoning = null,
        ?float      $confidence = null,
    ): void {
        // 1. Apply universe state impact
        if (!empty($result->universeImpact)) {
            $vec = $universe->state_vector ?? [];
            foreach ($result->universeImpact as $key => $delta) {
                if (str_contains($key, '.')) {
                    // Nested key e.g. 'metrics.myth_intensity'
                    [$group, $field] = explode('.', $key, 2);
                    $vec[$group][$field] = round(
                        max(0.0, min(1.0, ($vec[$group][$field] ?? 0.0) + $delta)),
                        6
                    );
                } else {
                    $vec[$key] = round(max(0.0, min(1.0, ($vec[$key] ?? 0.0) + $delta)), 6);
                }
            }
            $universe->update(['state_vector' => $vec]);
        }

        // 2. Persist biography entry
        if ($result->hasBiography()) {
            $actor->biography .= "\n- {$result->biographyEntry}";
            $this->actorRepository->save($actor);
        }

        // 3. Persist chronicle entry
        if ($result->hasChronicle()) {
            Chronicle::create(array_merge(
                $result->chronicleEntry,
                [
                    'universe_id' => $universe->id,
                    'from_tick'   => $tick,
                    'to_tick'     => $tick,
                ]
            ));
        }

        // 4. Record decision log
        AgentDecision::create([
            'actor_id'        => $actor->id,
            'universe_id'     => $universe->id,
            'tick'            => $tick,
            'action_type'     => $actionType,
            'utility_score'   => round($score, 6),
            'confidence'      => $confidence,
            'reasoning'       => $reasoning,
            'traits_snapshot' => $actor->traits,
            'impact'          => $result->universeImpact,
        ]);
    }

    // ── Default model resolution ────────────────────────────────────────────

    private function loadOrBuildDefaultModel(Universe $universe): DecisionModel
    {
        $row = UniverseDecisionModel::where('universe_id', $universe->id)
            ->orderByDesc('generation')
            ->first();

        if ($row) {
            return new DecisionModel(
                id:                $row->id,
                policyId:          $row->policy_id ?? '',
                universeId:        $universe->id,
                modelType:         ModelType::from($row->model_type),
                weightVector:      $row->weight_vector ?? [],
                interactionMatrix: $row->interaction_matrix ?? [],
                thresholdVector:   $row->threshold_vector ?? [],
                contextWeights:    $row->context_weights ?? [],
                generation:        $row->generation,
            );
        }

        // Build the classic hardcoded weights as a seed DecisionModel so legacy universes work
        $dim = count(ActorEntity::TRAIT_DIMENSIONS);
        $traitMap = array_flip(ActorEntity::TRAIT_DIMENSIONS);

        $seed = fn(array $spec) => $this->buildWeightVector($spec, $traitMap, $dim);

        $model = new DecisionModel(
            id:                (string) Str::uuid(),
            policyId:          '',
            universeId:        $universe->id,
            modelType:         ModelType::LINEAR,
            weightVector:      [
                'revolt'          => $seed(['Ambition' => 0.7, 'Coercion' => 0.6, 'Vengeance' => 0.8, 'Fear' => -0.5, 'Dominance' => 0.6]),
                'form_contract'   => $seed(['Solidarity' => 0.8, 'Loyalty' => 0.6, 'Empathy' => 0.5, 'Fear' => 0.4, 'Ambition' => -0.3]),
                'migrate'         => $seed(['Curiosity' => 0.7, 'RiskTolerance' => 0.8, 'Hope' => 0.5, 'Fear' => 0.6]),
                'trade'           => $seed(['Pragmatism' => 0.9, 'Solidarity' => 0.4, 'Ambition' => 0.5]),
                'suppress_revolt' => $seed(['Dominance' => 0.8, 'Coercion' => 0.7, 'Loyalty' => 0.5, 'Empathy' => -0.4]),
                'propagate_myth'  => $seed(['Dogmatism' => 0.8, 'Hope' => 0.6, 'Pride' => 0.5, 'Pragmatism' => -0.3]),
            ],
            interactionMatrix: [],
            thresholdVector:   [
                'revolt' => 1.2, 'form_contract' => 1.0, 'migrate' => 1.1,
                'trade' => 1.0, 'suppress_revolt' => 1.1, 'propagate_myth' => 1.0,
            ],
            contextWeights:    ['entropy_scale' => 0.0, 'myth_scale' => 0.0],
            generation:        1,
        );

        // Persist so next tick loads from DB
        UniverseDecisionModel::create([
            'id'                 => $model->id,
            'universe_id'        => $universe->id,
            'policy_id'          => null,
            'model_type'         => $model->modelType->value,
            'weight_vector'      => $model->weightVector,
            'interaction_matrix' => $model->interactionMatrix,
            'threshold_vector'   => $model->thresholdVector,
            'context_weights'    => $model->contextWeights,
            'generation'         => $model->generation,
        ]);

        return $model;
    }

    private function buildWeightVector(array $spec, array $traitMap, int $dim): array
    {
        $vec = array_fill(0, $dim, 0.0);
        foreach ($spec as $traitName => $weight) {
            if (isset($traitMap[$traitName])) {
                $vec[$traitMap[$traitName]] = $weight;
            }
        }
        return $vec;
    }
}

