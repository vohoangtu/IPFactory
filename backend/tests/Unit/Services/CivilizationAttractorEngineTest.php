<?php

namespace Tests\Unit\Services;

use App\Modules\Intelligence\Services\CivilizationAttractorEngine;
use App\Modules\Intelligence\Entities\Archetypes\Warlord;
use App\Modules\Intelligence\Entities\Archetypes\Technocrat;
use App\Modules\Intelligence\Entities\Archetypes\VillageElder;
use Tests\TestCase;

class CivilizationAttractorEngineTest extends TestCase
{
    private CivilizationAttractorEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new CivilizationAttractorEngine();
    }

    // ─── Dot Product Score ──────────────────────────────────────

    public function test_score_computes_dot_product_correctly(): void
    {
        $state = ['militarism' => 0.8, 'chaos' => 0.6, 'stability' => 0.2];
        $vector = ['militarism' => 0.9, 'chaos' => 0.7, 'stability' => -0.5];

        // 0.8*0.9 + 0.6*0.7 + 0.2*(-0.5) = 0.72 + 0.42 - 0.10 = 1.04
        $score = $this->engine->score($state, $vector);

        $this->assertEqualsWithDelta(1.04, $score, 0.001);
    }

    public function test_score_ignores_missing_state_dimensions(): void
    {
        $state = ['technology' => 0.9]; // missing 'stability'
        $vector = ['technology' => 0.9, 'stability' => 0.7];

        // 0.9*0.9 + 0*0.7 = 0.81
        $score = $this->engine->score($state, $vector);

        $this->assertEqualsWithDelta(0.81, $score, 0.001);
    }

    public function test_score_returns_zero_for_empty_vector(): void
    {
        $state = ['technology' => 0.9];
        $vector = [];

        $this->assertEquals(0.0, $this->engine->score($state, $vector));
    }

    // ─── Evaluate (Ranking) ─────────────────────────────────────

    public function test_evaluate_ranks_archetypes_by_score(): void
    {
        // Civilization state biased toward militarism + chaos
        $warState = [
            'militarism' => 0.9, 'chaos' => 0.8, 'stability' => 0.1,
            'technology' => 0.3, 'knowledge' => 0.2, 'tradition' => 0.7,
            'culture' => 0.5, 'trauma' => 0.6,
        ];

        $archetypes = [
            new Technocrat(),
            new Warlord(),
            new VillageElder(),
        ];

        $results = $this->engine->evaluate($warState, $archetypes);

        // Warlord should be first (high militarism/chaos alignment)
        $this->assertSame('Warlord', $results[0]['archetype']->getName());
        $this->assertGreaterThan($results[1]['score'], $results[0]['score']);
    }

    public function test_evaluate_technocrat_wins_in_stable_tech_world(): void
    {
        $techState = [
            'technology' => 0.9, 'stability' => 0.8, 'knowledge' => 0.7,
            'militarism' => 0.1, 'chaos' => 0.1, 'tradition' => 0.2,
            'culture' => 0.3, 'trauma' => 0.0,
        ];

        $archetypes = [
            new Warlord(),
            new Technocrat(),
            new VillageElder(),
        ];

        $results = $this->engine->evaluate($techState, $archetypes);

        $this->assertSame('Technocrat', $results[0]['archetype']->getName());
    }

    public function test_evaluate_village_elder_wins_in_traditional_world(): void
    {
        $traditionalState = [
            'tradition' => 0.9, 'stability' => 0.7, 'culture' => 0.8,
            'chaos' => 0.1, 'technology' => 0.2, 'militarism' => 0.2,
            'knowledge' => 0.3, 'trauma' => 0.0,
        ];

        $archetypes = [
            new Warlord(),
            new Technocrat(),
            new VillageElder(),
        ];

        $results = $this->engine->evaluate($traditionalState, $archetypes);

        $this->assertSame('VillageElder', $results[0]['archetype']->getName());
    }

    // ─── Attractor Vector ───────────────────────────────────────

    public function test_base_utility_uses_dot_product(): void
    {
        $warlord = new Warlord();
        $state = ['militarism' => 1.0, 'chaos' => 1.0, 'stability' => 0.0, 'trauma' => 1.0];

        // Expected: 1.0*0.9 + 1.0*0.7 + 0.0*(-0.5) + 1.0*0.4 = 2.0
        $this->assertEqualsWithDelta(2.0, $warlord->getBaseUtility($state), 0.001);
    }

    public function test_negative_attractor_reduces_score(): void
    {
        $warlord = new Warlord();
        // High stability reduces Warlord score (stability weight = -0.5)
        $stableState = ['militarism' => 0.5, 'chaos' => 0.3, 'stability' => 1.0, 'trauma' => 0.0];
        $unstableState = ['militarism' => 0.5, 'chaos' => 0.3, 'stability' => 0.0, 'trauma' => 0.0];

        $this->assertGreaterThan(
            $warlord->getBaseUtility($stableState),
            $warlord->getBaseUtility($unstableState)
        );
    }
}
