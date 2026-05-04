<?php

namespace App\Modules\Intelligence\Services;

use App\Contracts\Repositories\UniverseRepositoryInterface;
use App\Modules\Intelligence\Contracts\ActorRepositoryInterface;
use App\Modules\Intelligence\Entities\ActorEntity;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

/**
 * Behavior & Decision Engine (Tier 6).
 * Needs (hunger, safety, reproduction, social), goal from needs, Utility AI (score actions),
 * execution state (idle, eating, fleeing, mating, exploring). Cognitive modeling via DSL.
 * Stagger tick (actor_id % N === tick % N) for performance.
 */
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use function resource_path;
use function app;
use function config;
use function array_filter;
use function max;
use function file_get_contents;
use function exp;
use function mt_srand;
use function mt_rand;
use function is_array;
use function json_decode;
use function is_string;
use function in_array;

class ActorBehaviorEngine
{
    public const NEED_SURVIVAL     = 'survival';
    public const NEED_REPRODUCTION = 'reproduction';
    public const NEED_WEALTH       = 'wealth';
    public const NEED_POWER        = 'power';
    public const NEED_KNOWLEDGE    = 'knowledge';
    public const NEED_MEANING      = 'meaning';
    public const NEED_STATUS       = 'status';
    public const NEED_BELONGING    = 'belonging';

    public const ACTION_IDLE = 'idle';
    public const ACTION_EAT = 'eating';
    public const ACTION_FLEE = 'fleeing';
    public const ACTION_MATE = 'mating';
    public const ACTION_EXPLORE = 'exploring';
    public const ACTION_BATTLE = 'battle';
    public const ACTION_RESEARCH = 'research';
    public const ACTION_TRADE = 'trade';
    public const ACTION_MEDITATE = 'meditate';

    public function __construct(
        protected ActorRepositoryInterface $actorRepository,
        protected UniverseRepositoryInterface $universeRepository,
        protected \App\Modules\Narrative\Services\TraitMapper $traitMapper,
        protected \App\Modules\Simulation\Core\Engines\Meta\ActorDecisionEngine $decisionEngine,
        protected RuleVmService $ruleVm,
        protected \App\Modules\Simulation\Services\Society\VocationActionEngine $vocationActionEngine,
        protected \App\Modules\Intelligence\Services\GreatPersonEngine $greatPersonEngine,
        protected \App\Modules\Institutions\Actions\SpawnSupremeEntityAction $spawnAction
    ) {}

    /**
     * Run behavior decision for actors this tick using standardized WorldState.
     */
    public function runWithState(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, int $currentTick): void
    {
        $interval = (int) config('worldos.intelligence.behavior_tick_interval', 1);
        if ($interval <= 0 || $currentTick % $interval !== 0) {
            return;
        }

        $universeId = (int) $state->get('universe_id', 0);
        $actors = $state->getActorEntities();
        $alive = array_filter($actors, fn($a) => $a->isAlive);
        if (empty($alive)) {
            return;
        }

        $stagger = max(1, (int) config('worldos.intelligence.behavior_stagger_modulus', 3));
        $collapseActive = $this->isCollapseActive($state->toArray(), $currentTick);
        $seed = (int) ($state->get('seed', 0)) + $universeId * 31;
        $updated = 0;

        foreach ($alive as $actor) {
            if (($actor->id ?? 0) % $stagger !== $currentTick % $stagger) {
                continue;
            }
            $this->decideAndApplyWithState($actor, $state, $currentTick, $collapseActive, $seed);
            $updated++;
        }

        if ($updated > 0) {
            Log::debug("ActorBehaviorEngine: Universe {$universeId} tick {$currentTick}, {$updated} actors updated in state pool");
        }
    }

    public function evaluate(Universe $universe, int $currentTick): void
    {
        // Deprecated: Pipeline handles runWithState
    }

    private function decideAndApplyWithState(
        ActorEntity $actor,
        \App\Modules\Simulation\Core\Runtime\State\WorldState $state,
        int $tick,
        bool $collapseActive,
        int $seed
    ): void {
        $metrics = $actor->metrics ?? [];
        $traits = $actor->traits ?? [];
        $archetype = $actor->archetype ?? 'Commoner';

        // 1. Prepare State for Rule VM
        $classifier = app(\App\Modules\Intelligence\Domain\Archetype\ArchetypeClassifier::class);
        $definition = $classifier->getDefinition($archetype);
        $motivation = $definition?->motivationVector ?? [];
        $culture = \App\Modules\Intelligence\Services\CultureEngine::getCultureForActor($metrics);
        
        $fields = $state->getFields();
        $causalIntegrity = (float) ($state->get('causal_integrity', 1.0));

        $vmState = [
            'energy'           => (float) ($metrics['energy'] ?? 100),
            'maxEnergy'        => (float) ($metrics['max_energy'] ?? 200),
            'starving'         => !empty($metrics['starving']),
            'generation'       => (int) ($metrics['generation'] ?? 1),
            'collapse_active'  => $collapseActive,
            'causal_integrity' => $causalIntegrity,
            'is_heroic'        => (bool) ($actor->isHeroic ?? false),
            'heroic_type'      => $actor->heroicType ?? '',
            'traits'           => $actor->traits ?? [],
            
            // Capabilities (Physical & Mental)
            'intellect'        => (float) ($metrics['intellect'] ?? 0.5),
            'charisma'         => (float) ($metrics['charisma'] ?? 0.5),
            'creativity'       => (float) ($metrics['creativity'] ?? 0.5),
            'authority'        => (float) ($metrics['authority'] ?? 0.5),
            
            // Belief flags
            'has_religion'     => !empty($metrics['religion_id']),
            'has_causal_trajectory_belief' => !empty($metrics['has_trajectory']),
            'legend_level'     => (int) ($metrics['legend_level'] ?? 0),

            // Archetype motivations
            'arch_survival'    => (float) ($motivation['survival'] ?? 0.5),
            'arch_reproduction'=> (float) ($motivation['reproduction'] ?? 0.5),
            'arch_wealth'      => (float) ($motivation['wealth'] ?? 0.5),
            'arch_power'       => (float) ($motivation['power'] ?? 0.5),
            'arch_knowledge'   => (float) ($motivation['knowledge'] ?? 0.5),
            'arch_meaning'     => (float) ($motivation['meaning'] ?? 0.5),
            'arch_status'      => (float) ($motivation['status'] ?? 0.5),
            'arch_belonging'   => (float) ($motivation['belonging'] ?? 0.5),
            // Global fields resonance (Phase 30: 8-Attractor aligned)
            'field_survival'     => (float) ($fields['survival'] ?? 0.5),
            'field_reproduction' => (float) ($fields['reproduction'] ?? 0.5),
            'field_wealth'       => (float) ($fields['wealth'] ?? 0.5),
            'field_power'        => (float) ($fields['power'] ?? 0.5),
            'field_knowledge'    => (float) ($fields['knowledge'] ?? 0.5),
            'field_meaning'      => (float) ($fields['meaning'] ?? 0.5),
            'field_status'       => (float) ($fields['status'] ?? 0.5),
            'field_belonging'    => (float) ($fields['belonging'] ?? 0.5),

            // Culture (8D Memes)
            'meme_survival'    => (float) ($culture[\App\Modules\Intelligence\Services\CultureEngine::MEME_SURVIVAL] ?? 0.5),
            'meme_reproduction'=> (float) ($culture[\App\Modules\Intelligence\Services\CultureEngine::MEME_REPRODUCTION] ?? 0.5),
            'meme_wealth'      => (float) ($culture[\App\Modules\Intelligence\Services\CultureEngine::MEME_WEALTH] ?? 0.5),
            'meme_power'       => (float) ($culture[\App\Modules\Intelligence\Services\CultureEngine::MEME_POWER] ?? 0.5),
            'meme_knowledge'   => (float) ($culture[\App\Modules\Intelligence\Services\CultureEngine::MEME_KNOWLEDGE] ?? 0.5),
            'meme_meaning'     => (float) ($culture[\App\Modules\Intelligence\Services\CultureEngine::MEME_MEANING] ?? 0.5),
            'meme_status'      => (float) ($culture[\App\Modules\Intelligence\Services\CultureEngine::MEME_STATUS] ?? 0.5),
            'meme_belonging'   => (float) ($culture[\App\Modules\Intelligence\Services\CultureEngine::MEME_BELONGING] ?? 0.5),
            'tick'             => (int) $tick,
        ];

        // 2. Evaluate Cognitive Model via DSL
        $dslPath = resource_path('worldos_rules/intel/cognitive_models.dsl');
        $dsl = @file_get_contents($dslPath) ?: '';
        $result = $this->ruleVm->evaluateRawState($vmState, $dsl);
        $finalState = $result['state'] ?? [];

        // 3. Action Selection
        // Macro-narrative actions (Phase 43 integration)
        $decisionDist = $this->decisionEngine->getActionDistribution($actor, $state, $tick);
        
        $scores = [
            self::ACTION_IDLE     => (float) ($finalState['score_idle'] ?? 0.1),
            self::ACTION_EAT      => (float) ($finalState['score_eat'] ?? 0.0),
            self::ACTION_FLEE     => (float) ($finalState['score_flee'] ?? 0.0),
            self::ACTION_MATE     => (float) ($finalState['score_mate'] ?? 0.0),
            self::ACTION_EXPLORE  => ($decisionDist['explore'] ?? 0.0) + (float) ($finalState['score_explore'] ?? 0.1),
            self::ACTION_BATTLE   => ($decisionDist['war'] ?? 0.0) + (float) ($finalState['score_battle'] ?? 0.0),
            self::ACTION_RESEARCH => ($decisionDist['teach'] ?? 0.0) + (float) ($finalState['score_research'] ?? 0.0),
            self::ACTION_TRADE    => ($decisionDist['trade'] ?? 0.0) + (float) ($finalState['score_trade'] ?? 0.0),
            self::ACTION_MEDITATE => ($decisionDist['meditate'] ?? 0.0) + (float) ($finalState['score_meditate'] ?? 0.0),
        ];

        // Apply faction bias (still calculated in PHP for simplicity of reference)
        $factions = $state->get('factions', []);
        $factionBias = $this->getFactionBias((int)($actor->id ?? 0), $factions);
        foreach ($factionBias as $act => $mul) {
            if (isset($scores[$act])) $scores[$act] *= $mul;
        }

        $action = $this->selectAction($scores, $seed, $actor->id ?? 0, $tick);

        // 3.1. Process Vocation-Specific Action (DSL Expansion)
        $this->vocationActionEngine->process($actor, $state, $tick);

        // 4. Update Actor Metrics
        $intentTag = $this->traitMapper->getIntentTag($traits);
        
        $metrics['behavior_state'] = $action;
        $metrics['needs'] = [
            self::NEED_SURVIVAL     => (float) ($finalState['mSurvival'] ?? $finalState['hunger'] ?? 0.5),
            self::NEED_REPRODUCTION => (float) ($finalState['mRepro'] ?? $finalState['reproduction'] ?? 0.2),
            self::NEED_WEALTH       => (float) ($finalState['mWealth'] ?? 0.5),
            self::NEED_POWER        => (float) ($finalState['mPower'] ?? 0.5),
            self::NEED_KNOWLEDGE    => (float) ($finalState['mKnowledge'] ?? 0.5),
            self::NEED_MEANING      => (float) ($finalState['mMeaning'] ?? 0.5),
            self::NEED_STATUS       => (float) ($finalState['mStatus'] ?? 0.5),
            self::NEED_BELONGING    => (float) ($finalState['mBelonging'] ?? $finalState['belonging_need'] ?? 0.5),
        ];
        // Đồng bộ motivation profile để frontend đọc trực tiếp
        $metrics['motivation'] = $metrics['needs'];
        $metrics['reasoning'] = $intentTag;
        $metrics['last_behavior_tick'] = $tick;
        $actor->metrics = $metrics;

        // Trace
        if (!$actor->id) return;
        \App\Modules\Intelligence\Models\ActorEvent::create([
            'actor_id'   => $actor->id,
            'tick'       => $tick,
            'event_type' => 'behavior_decision',
            'context'    => [
                'action'    => $action,
                'intent'    => $intentTag,
                'archetype' => $archetype,
                'scores'    => $scores,
                'vm_state'  => $vmState // FULL SNAPSHOT OF THE WORLD/ACTOR FOR NARRATIVE AI
            ]
        ]);

        // 5. Great Person Crystallization (Cực hiếm)
        if (!$actor->isHeroic && $tick % 10 === 0) {
            $universeId = (int) $state->get('universe_id', 0);
            $universe = \App\Modules\World\Models\Universe::find($universeId);
            if ($universe) {
                $networkDensity = (float) ($state->get('metrics.social_cohesion', 0.5));
                $type = $this->greatPersonEngine->evaluateCrystallization(
                    $actor->toState(),
                    $universe,
                    $fields,
                    $networkDensity
                );

                if ($type) {
                    $this->spawnAction->handle($universe, $tick, [
                        'name' => $actor->name,
                        'entity_type' => 'great_person_' . strtolower($type),
                        'domain' => \App\Modules\Intelligence\Services\GreatPersonEngine::TYPES[$type] ?? $type,
                        'description' => "Cá nhân xuất chúng đã bộc lộ thiên tư vĩ đại trong thời đại biến động.",
                        'power_level' => 0.8,
                    ], $actor->id);

                    // Update actor status in state
                    $actor->isHeroic = true;
                    $actor->heroicType = $type;
                }
            }
        }
    }

    private function getFactionBias(int $actorId, array $factions): array
    {
        foreach ($factions as $f) {
            $members = $f['member_actor_ids'] ?? [];
            if (in_array($actorId, $members)) {
                return $f['collective_decision_bias'] ?? [];
            }
        }
        return [];
    }


    private function selectAction(array $scores, int $seed, int $actorId, int $tick): string
    {
        // Probability-based selection (Softmax-ish) for variability
        $total = 0;
        foreach ($scores as $s) {
            $total += exp($s * 5); // T=0.2 factor for sharpness
        }

        // Deterministic Rand for this actor/tick
        mt_srand($seed + $actorId + $tick);
        $rand = mt_rand(0, 1000000) / 1000000.0;
        
        $current = 0;
        foreach ($scores as $action => $s) {
            $prob = exp($s * 5) / $total;
            $current += $prob;
            if ($rand <= $current) {
                return $action;
            }
        }

        return self::ACTION_IDLE;
    }


    private function isCollapseActive(array $stateVector, int $tick): bool
    {
        $collapse = $stateVector['ecological_collapse'] ?? [];
        if (!is_array($collapse) || empty($collapse['active'])) {
            return false;
        }
        $until = (int) ($collapse['until_tick'] ?? 0);
        return $tick <= $until;
    }

    private function getStateVector(Universe $universe): array
    {
        $sv = $universe->state_vector;
        if (is_string($sv)) {
            $sv = json_decode($sv, true) ?? [];
        }
        return is_array($sv) ? $sv : [];
    }
}





