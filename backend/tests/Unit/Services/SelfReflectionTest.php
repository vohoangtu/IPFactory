<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Modules\Simulation\Models\CivilizationAttractor;
use App\Modules\Intelligence\Models\AiMemory;
use App\Modules\Intelligence\Services\Analysis\SelfReflectionEngine;
use App\Modules\Intelligence\Services\Analysis\PatternAnalyzer;
use App\Modules\Intelligence\Services\Analysis\RuleExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SelfReflectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_reflection_discovers_new_attractors()
    {
        // 1. Create dummy memories (trajectories with a clear pattern)
        // Pattern: Transition to 'Warlord' always happens after 'stability' < 0.2
        $history = [
            ['tick' => 10, 'winner' => 'VillageElder', 'state' => ['stability' => 0.5]],
            ['tick' => 11, 'winner' => 'VillageElder', 'state' => ['stability' => 0.1]], // Precursor
            ['tick' => 12, 'winner' => 'Warlord', 'state' => ['stability' => 0.05]],   // Transition
        ];

        AiMemory::create([
            'universe_id' => 1,
            'scope' => 'civilization',
            'category' => 'trajectory',
            'content' => json_encode($history),
        ]);

        // 2. Setup Engine
        $analyzer = new PatternAnalyzer();
        $extractor = new RuleExtractor();
        $engine = new SelfReflectionEngine($analyzer, $extractor);

        // 3. Reflect
        $memories = AiMemory::all();
        $discoveredCount = $engine->reflect($memories->all());

        // 4. Assertions
        $this->assertGreaterThan(0, $discoveredCount);
        $this->assertDatabaseHas('civilization_attractors', [
            'name' => 'Precursor for Warlord',
        ]);

        $attractor = CivilizationAttractor::where('name', 'Precursor for Warlord')->first();
        $rules = $attractor->activation_rules;
        
        // Find the stability rule
        $stabilityRule = collect($rules)->firstWhere('key', 'stability');
        $this->assertEquals(0.1, $stabilityRule['value']);
        $this->assertEquals('<', $stabilityRule['op']);
    }
}
