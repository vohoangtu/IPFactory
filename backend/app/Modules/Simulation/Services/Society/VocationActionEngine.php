<?php

namespace App\Modules\Simulation\Services\Society;

use App\Modules\Intelligence\Entities\ActorEntity;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\RuleVM\RuleVmService;
use App\Modules\Simulation\Services\Society\VocationEngine;
use App\Modules\Simulation\Services\Core\RuleMutationService;
use Illuminate\Support\Facades\Log;

/**
 * VocationActionEngine bridges actor behavior states with specific vocation skills.
 * It selects the best skill for the current action and executes it via Rust DSL.
 */
class VocationActionEngine
{
    public function __construct(
        protected VocationEngine $vocationEngine,
        protected RuleVmService $ruleVm,
        protected RuleMutationService $mutationService
    ) {}

    /**
     * Process vocation-specific actions for an actor based on their behavior state.
     */
    public function process(ActorEntity $actor, WorldState $state, int $tick): void
    {
        $vocationId = $actor->vocationId;
        if (!$vocationId) return;

        $behaviorState = $actor->metrics['behavior_state'] ?? 'idle';
        
        // Map behavior states to skill categories
        $skillId = $this->selectSkillForBehavior($actor, $behaviorState);
        if (!$skillId) return;

        $skill = $this->vocationEngine->getSkill($vocationId, $skillId);
        if (!$skill || !isset($skill['rule'])) return;

        // 1. Resolve DSL Rule (Check for Mutations)
        $virtualPath = "vocation://{$vocationId}/{$skillId}.dsl";
        $ruleDsl = $this->mutationService->getMutatedContent($virtualPath) ?: $skill['rule'];

        // 2. Chance to Mutate (Autonomous Evolution) if Entropy is high
        $entropy = (float) $state->get('entropy', 0.5);
        if ($entropy > 0.8 && mt_rand(0, 100) < 5) {
            $ruleDsl = $this->attemptRuleMutation($virtualPath, $ruleDsl, $entropy, $tick);
        }

        // 3. Execute the Rust DSL rule
        Log::debug("VocationActionEngine: Actor {$actor->id} executing skill {$skillId}", [
            'vocation' => $vocationId,
            'behavior' => $behaviorState
        ]);

        $context = [
            'actor_id' => $actor->id,
            'skill_id' => $skillId,
            'resonance' => $actor->metrics['resonance'] ?? 1.0,
            'bloodline' => $actor->lineage_id ?? 'NONE'
        ];

        $this->ruleVm->evaluateAndApplyWithDsl($state, $ruleDsl, $tick, $context);
        
        // 4. Special Action: Synthesis Discovery (Autonomous Evolution)
        if (in_array($behaviorState, ['research', 'meditate'])) {
            $this->handleSkillDiscovery($actor, $vocationId, $tick);
        }

        // 5. Record Skill History (for Combos)
        $this->recordSkillHistory($actor, $skillId);
    }

    private function handleSkillDiscovery(ActorEntity $actor, string $vocationId, int $tick): void
    {
        // Try discovery based on current state and vocation
        $result = $this->vocationEngine->attemptSynthesis($actor, $vocationId, 'RANDOM_ACCIDENT');
        
        if (isset($result['success']) && $result['success'] && isset($result['skill'])) {
            $newSkillId = $result['skill']['id'];
            $knownSkills = $actor->metrics['known_skills'] ?? [];
            
            if (!in_array($newSkillId, $knownSkills)) {
                $knownSkills[] = $newSkillId;
                $actor->metrics['known_skills'] = $knownSkills;
                
                Log::info("VocationActionEngine: Actor {$actor->id} discovered new skill {$newSkillId} via {$actor->metrics['behavior_state']}");
                
                // Emit Discovery Event
                try {
                    \App\Modules\Intelligence\Models\ActorEvent::create([
                        'actor_id'   => $actor->id,
                        'tick'       => $tick,
                        'event_type' => 'skill_discovery',
                        'context'    => [
                            'skill_id' => $newSkillId,
                            'vocation' => $vocationId,
                            'method'   => $actor->metrics['behavior_state']
                        ]
                    ]);
                } catch (\Exception $e) {
                    Log::warning("VocationActionEngine: Failed to log ActorEvent: " . $e->getMessage());
                }
            }
        }
    }

    private function selectSkillForBehavior(ActorEntity $actor, string $behavior): ?string
    {
        // Simple mapping for now. Can be expanded to use motivation scores.
        return match ($behavior) {
            'battle'   => 'iron_fist', // Example: logic to pick strongest battle skill
            'trade'    => 'bargain',
            'research' => 'meditation',
            default    => null
        };
    }

    private function recordSkillHistory(ActorEntity $actor, string $skillId): void
    {
        $history = $actor->metrics['skill_history'] ?? [];
        $history[] = $skillId;
        
        // Keep only last 5 skills
        if (count($history) > 5) {
            array_shift($history);
        }
        
        $actor->metrics['skill_history'] = $history;
    }

    private function attemptRuleMutation(string $path, string $dsl, float $entropy, int $tick): string
    {
        // Simple heuristic mutation: search for "power: X" or "health: -X" and drift them
        // This simulates the skill morphing over time due to world instability
        $mutatedDsl = preg_replace_callback('/(power|health|energy|intellect):\s*(-?\d+(\.\d+)?)/', function($matches) use ($entropy) {
            $key = $matches[1];
            $val = (float) $matches[2];
            $drift = (mt_rand(0, 20) - 10) / 100 * $entropy; // max 10% drift scaled by entropy
            $newVal = round($val * (1 + $drift), 3);
            return "{$key}: {$newVal}";
        }, $dsl);

        if ($mutatedDsl !== $dsl) {
            $this->mutationService->applyMutation($path, $mutatedDsl, [
                'source' => 'vocation_action_engine',
                'entropy' => $entropy,
                'type' => 'autonomous_evolution',
                'tick' => $tick,
            ]);
            Log::info("VocationActionEngine: Skill rule mutated at {$path}");
        }

        return $mutatedDsl;
    }
}
