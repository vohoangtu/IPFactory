<?php

namespace App\Modules\Simulation\Vocation\Services;

use App\Modules\Simulation\Vocation\Entities\VocationEntity;
use App\Modules\Simulation\Vocation\Entities\SkillEntity;
use App\Modules\Simulation\Vocation\DSL\ExpressionEngine;
use App\Modules\Simulation\Vocation\DSL\ExpressionContext;
use App\Modules\Simulation\Vocation\Contracts\VocationRepositoryInterface;
use App\Modules\Simulation\Vocation\Contracts\SkillRepositoryInterface;
use App\Modules\Simulation\Vocation\Contracts\ActorMasteryRepositoryInterface;
use Illuminate\Support\Facades\Log;

class VocationEngine
{
    public function __construct(
        private VocationRepositoryInterface $vocationRepository,
        private SkillRepositoryInterface $skillRepository,
        private ActorMasteryRepositoryInterface $masteryRepository,
        private ExpressionEngine $expressionEngine,
        private ElementInteractionService $elementService
    ) {}

    /**
     * Execute a skill for an actor.
     */
    public function executeSkill(string $actorId, int $skillId, array $worldContext = []): ExecutionResult
    {
        $skill = $this->skillRepository->findById($skillId);
        if (!$skill) {
            throw new \Exception("Skill not found: $skillId");
        }

        $mastery = $this->masteryRepository->findByActorAndVocation($actorId, $skill->vocationId);
        
        // 1. Resolve Element Interactions (Data Provision only)
        $elementResonance = $this->elementService->calculateResonance($actorId, $skill, $worldContext);
        
        // 2. Prepare Context for Rust Rule VM
        $context = new ExpressionContext([
            'tick' => $worldContext['tick'] ?? 0,
            'actor' => [
                'id' => $actorId,
                'mastery_level' => $mastery ? $mastery->level : 1,
                'experience' => $mastery ? $mastery->experience : 0,
                'strength' => $worldContext['strength'] ?? 10,
            ],
            'skill' => [
                'id' => $skill->id,
                'name' => $skill->name,
            ],
            'element_resonance' => $elementResonance,
            'base_attack' => $worldContext['base_attack'] ?? 10,
        ]);

        // 3. Evaluate Skill via Rust Rule VM (gRPC)
        $outputs = $this->expressionEngine->evaluate($skill->ruleDsl, $context);
        
        // 4. Process Results
        $result = new ExecutionResult();
        foreach ($outputs as $output) {
            // Handle tagged style (from stub) or enum style (from Rust)
            $type = $output['type'] ?? array_key_first($output);
            $data = $output['type'] ? $output : $output[$type];

            switch ($type) {
                case 'Calc':
                    if (($data['path'] ?? $data['name'] ?? '') === 'execution_result.value' || ($data['name'] ?? '') === 'damage') {
                        $result->value = (float)$data['value'];
                    }
                    break;
                case 'SetPath':
                    if (($data['path'] ?? '') === 'execution_result.status') {
                        $result->success = ($data['value'] === 'success');
                    }
                    if (($data['path'] ?? '') === 'execution_result.value') {
                        $result->value = (float)$data['value'];
                    }
                    break;
                case 'Event':
                    $result->addEffect([
                        'type' => 'event',
                        'name' => $data['name'] ?? 'unknown',
                        'payload' => $data['payload'] ?? []
                    ]);
                    break;
            }
        }

        return $result;
    }
}
