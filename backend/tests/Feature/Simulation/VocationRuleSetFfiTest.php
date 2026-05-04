<?php

namespace Tests\Feature\Simulation;

use Tests\TestCase;
use App\Services\Simulation\FfiActorEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VocationRuleSetFfiTest extends TestCase
{
    private FfiActorEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new FfiActorEngine();
    }

    /** @test */
    public function it_can_calculate_vocation_alignment_via_ffi()
    {
        $actorMotivation = [
            "creation" => 0.8,
            "destruction" => 0.1,
            "order" => 0.7,
            "chaos" => 0.2,
            "self_preservation" => 0.5,
            "altruism" => 0.9,
            "physical" => 0.3,
            "metaphysical" => 0.6
        ];

        $vocationProfile = [
            "creation" => 0.9,
            "destruction" => 0.0,
            "order" => 0.8,
            "chaos" => 0.1,
            "self_preservation" => 0.4,
            "altruism" => 1.0,
            "physical" => 0.2,
            "metaphysical" => 0.7
        ];

        $score = $this->engine->calculateVocationAlignment($actorMotivation, $vocationProfile);
        
        $this->assertGreaterThan(0.0, $score);
        // dot product calculation: 0.8*0.9 + 0.1*0 + 0.7*0.8 + 0.2*0.1 + 0.5*0.4 + 0.9*1.0 + 0.3*0.2 + 0.6*0.7
        // 0.72 + 0 + 0.56 + 0.02 + 0.2 + 0.9 + 0.06 + 0.42 = 2.88
        $this->assertEqualsWithDelta(2.88, $score, 0.01);
    }

    /** @test */
    public function it_can_calculate_combined_gravity_via_ffi()
    {
        $rulesets = [
            [
                "id" => "realistic_modern",
                "tier" => 0,
                "physics" => ["gravity" => 1.0, "entropy" => true, "reality_stability" => 1.0],
                "energy" => ["ambient_density" => 0.0, "system_type" => "none"]
            ],
            [
                "id" => "wuxia_jianghu",
                "tier" => 2,
                "physics" => ["gravity" => 0.8, "entropy" => true, "reality_stability" => 1.0],
                "energy" => ["ambient_density" => 0.2, "system_type" => "internal_qi"]
            ]
        ];

        $gravity = $this->engine->getCombinedGravity($rulesets);
        
        // (1.0 + 0.8) / 2 = 0.9
        $this->assertEqualsWithDelta(0.9, $gravity, 0.01);
    }
}
