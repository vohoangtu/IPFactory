<?php

namespace Tests\Unit\Psychology;

use App\Modules\Psychology\Services\GoapPlanner;
use App\Modules\Psychology\ValueObjects\PsychologicalState;
use Tests\TestCase;

class GoapPlannerTest extends TestCase
{
    private GoapPlanner $planner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->planner = new GoapPlanner();
    }

    public function test_survive_goal_with_high_fear_generates_flee_sequence(): void
    {
        $state = new PsychologicalState(0.9, 0.5, 0.0, 0.0, 0.0, 0.0); // Fear = 0.9
        
        $sequence = $this->planner->planSequence($state, ['type' => 'survive']);

        $this->assertEquals(['flee', 'isolate', 'passive'], $sequence);
    }

    public function test_survive_goal_with_low_fear_generates_defend_sequence(): void
    {
        $state = new PsychologicalState(0.2, 0.5, 0.0, 0.0, 0.0, 0.0); // Fear = 0.2
        
        $sequence = $this->planner->planSequence($state, ['type' => 'survive']);

        $this->assertEquals(['defend', 'resist'], $sequence);
    }

    public function test_belonging_goal_with_low_trust_generates_observe_sequence(): void
    {
        $state = new PsychologicalState(trust: 0.2); // Trust = 0.2
        
        $sequence = $this->planner->planSequence($state, ['type' => 'belonging']);

        $this->assertEquals(['observe', 'passive'], $sequence);
    }

    public function test_unknown_goal_generates_idle_or_wander(): void
    {
        $highStress = new PsychologicalState(stress: 0.8);
        $sequence = $this->planner->planSequence($highStress, ['type' => 'unknown']);
        $this->assertEquals(['wander'], $sequence);

        $lowStress = new PsychologicalState(stress: 0.2);
        $sequence2 = $this->planner->planSequence($lowStress, ['type' => 'unknown']);
        $this->assertEquals(['idle'], $sequence2);
    }
}
