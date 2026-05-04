<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Simulation\Core\Support\SimulationRandom;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use function config;
use function array_sum;
use function round;
use function array_keys;
use function array_key_last;
use function in_array;

/**
 * ActorDecisionEngine — Phase 2.
 * Input: traits, capabilities, environment (entropy, stability, war_pressure, optional belief), age, culture.
 * Output: action_distribution [action_type => probability]. Roll yields one action.
 * Belief (from narrative loop): has_religion, has_causal_trajectory_belief, legend_level — adjust weights.
 */
class ActorDecisionEngine
{
    public function __construct(
        protected RuleVmService $ruleVm
    ) {}

    /** Trait indices: Dom=0, Amb=1, Cur=8, Rsk=10, Pra=7, Dog=9, Coe=2, Emp=4. */
    public function getActionDistribution(
        \App\Modules\Intelligence\Entities\ActorEntity $actor,
        \App\Modules\Simulation\Core\Runtime\State\WorldState $state,
        int $currentTick
    ): array {
        $actions = config('worldos.actor_decision.action_types', [
            'write', 'teach', 'explore', 'war', 'meditate', 'create_religion', 'build', 'govern', 'trade', 'rest',
        ]);

        // 1. Prepare State for Rule VM from Manifold
        $metrics = $actor->metrics ?? [];
        $traits = $actor->traits ?? [];
        $historicalPhase = $state->get('timeline.historical_phase', 'NORMAL');
        $resonanceField = (float) $state->get('resonance_field', 0.0);
        $fields = $state->getFields();

        $vmState = [
            'traits'           => $traits,
            'intellect'        => (float) ($metrics['intellect'] ?? 0.5),
            'charisma'        => (float) ($metrics['charisma'] ?? 0.5),
            'creativity'       => (float) ($metrics['creativity'] ?? 0.5),
            'authority'        => (float) ($metrics['authority'] ?? 0.5),
            'causal_integrity' => (float) ($state->get('causal_integrity', 1.0)),
            'entropy'          => (float) ($state->get('entropy', 0.5)),
            'stability'        => (float) ($state->get('stability_index', 0.5)),
            'resonance_field'  => $resonanceField,
            'historical_phase' => $historicalPhase,
            
            // Belief flags
            'has_religion'     => !empty($metrics['religion_id']),
            'has_causal_trajectory_belief' => !empty($metrics['has_trajectory']),
            'legend_level'     => (int) ($metrics['legend_level'] ?? 0),
            
            'energy'           => (float) ($metrics['energy'] ?? 100.0),
            'maxEnergy'        => (float) ($metrics['max_energy'] ?? 200.0),
            'starving'         => !empty($metrics['starving']),
            
            // Field resonances (Phase 29/43 integration)
            'field_knowledge'  => (float) ($fields['knowledge_field'] ?? 0.0),
            'field_meaning'    => (float) ($fields['meaning_field'] ?? 0.0),
            'field_power'      => (float) ($fields['power_field'] ?? 0.0),
            'field_status'     => (float) ($fields['status_field'] ?? 0.0),
            'field_belonging'  => (float) ($fields['belonging_field'] ?? 0.0),
            'field_reproduction'=> (float) ($fields['reproduction_field'] ?? 0.0),
        ];

        // 2. Evaluate Cognitive Model via Pure State DSL
        $state->set('actor_decision_input', $vmState);
        $this->ruleVm->evaluateAndApplyWithDsl($state, 'intel/cognitive_models', $currentTick);

        // 3. Extract Scores from WorldState
        $finalState = $state->get('actor_decision_output', []);

        // 3. Extract Scores for Narrative Actions
        $scores = [];
        $scores['write']   = (float) ($finalState['score_write'] ?? 0.1);
        $scores['teach']   = (float) ($finalState['score_teach'] ?? 0.1);
        $scores['explore'] = (float) ($finalState['score_explore'] ?? 0.1);
        $scores['war']     = (float) ($finalState['score_war'] ?? 0.1);
        $scores['meditate']= (float) ($finalState['score_meditate'] ?? 0.1);
        $scores['create_religion'] = (float) ($finalState['score_create_religion'] ?? 0.1);
        $scores['build']   = (float) ($finalState['score_build'] ?? 0.1);
        $scores['govern']  = (float) ($finalState['score_govern'] ?? 0.1);
        $scores['trade']   = (float) ($finalState['score_trade'] ?? 0.1);
        $scores['rest']    = 0.2; // default sink

        $total = (float) array_sum($scores);
        if ($total <= 0) {
            $total = 1.0;
        }
        $dist = [];
        foreach ($scores as $action => $s) {
            $dist[$action] = round($s / $total, 4);
        }
        // Normalize to sum 1
        $sum = array_sum($dist);
        if ($sum > 0) {
            foreach ($dist as $k => $v) {
                $dist[$k] = round($v / $sum, 4);
            }
        }
        return $dist;
    }

    /**
     * Roll one action from distribution using seeded RNG.
     */
    public function rollAction(array $actionDistribution, SimulationRandom $rng): string
    {
        $r = $rng->nextFloat();
        $cum = 0.0;
        foreach ($actionDistribution as $action => $prob) {
            $cum += $prob;
            if ($r < $cum) {
                return $action;
            }
        }
        $keys = array_keys($actionDistribution);
        return $keys[array_key_last($keys)] ?? 'rest';
    }

    /** Actions that may trigger artifact creation. */
    public function isArtifactEligibleAction(string $action): bool
    {
        return in_array($action, ['write', 'create_religion', 'build'], true);
    }
}




