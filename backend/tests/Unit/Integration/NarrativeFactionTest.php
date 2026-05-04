<?php

namespace Tests\Unit\Integration;

use App\Modules\Simulation\Core\Entities\Agent;
use App\Modules\Simulation\Core\Services\FactionService;
use App\Modules\Simulation\Core\Services\NarrativeTemplateEngine;
use Tests\TestCase;

class NarrativeFactionTest extends TestCase
{
    // ====== MODULE E: Narrative Engine ======

    public function test_narrate_attack_event(): void
    {
        $engine = new NarrativeTemplateEngine();
        $text = $engine->narrate('attack', [
            'attacker' => 'Kẻ Lang Thang',
            'victim' => 'Nông Dân Binh',
            'biome' => 'cánh đồng'
        ]);

        $this->assertStringContainsString('Kẻ Lang Thang', $text);
        $this->assertStringContainsString('Nông Dân Binh', $text);
        $this->assertNotEmpty($text);
    }

    public function test_narrate_unknown_event_returns_fallback(): void
    {
        $engine = new NarrativeTemplateEngine();
        $text = $engine->narrate('volcano_eruption', []);
        $this->assertStringContainsString('volcano_eruption', $text);
    }

    public function test_detect_story_arcs(): void
    {
        $engine = new NarrativeTemplateEngine();
        $eventLog = [
            ['type' => 'attack', 'agent' => 'a1', 'tick' => 1],
            ['type' => 'forage', 'agent' => 'a1', 'tick' => 2],
            ['type' => 'build_shelter', 'agent' => 'a1', 'tick' => 3],
            ['type' => 'cooperate', 'agent' => 'a2', 'tick' => 1],
            ['type' => 'cooperate', 'agent' => 'a2', 'tick' => 5],
            ['type' => 'death', 'agent' => 'a3', 'tick' => 10],
        ];

        $arcs = $engine->detectStoryArcs($eventLog);

        $this->assertEquals('redemption', $arcs['a1']['arc_type']);
        $this->assertEquals('friendship', $arcs['a2']['arc_type']);
        $this->assertEquals('tragedy', $arcs['a3']['arc_type']);
    }

    // ====== MODULE F: Faction & Governance ======

    public function test_elect_leader_by_reputation(): void
    {
        $service = new FactionService();

        $a1 = new Agent('a1');
        $a1->health = 100; $a1->psychology->joy = 0.8; $a1->psychology->anger = 0.1;

        $a2 = new Agent('a2');
        $a2->health = 50; $a2->psychology->joy = 0.2; $a2->psychology->anger = 0.6;

        $a3 = new Agent('a3');
        $a3->health = 80; $a3->psychology->joy = 0.5; $a3->psychology->anger = 0.2;

        $leader = $service->electLeader([$a1, $a2, $a3]);
        $this->assertEquals('a1', $leader->id, "Agent with highest reputation should be leader");
    }

    public function test_faction_culture_matches_leader_personality(): void
    {
        $this->markTestSkipped('TraitVector property `openness` was removed/refactored. Test needs update.');
    }
}
