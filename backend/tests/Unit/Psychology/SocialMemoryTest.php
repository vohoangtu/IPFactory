<?php

namespace Tests\Unit\Psychology;

use App\Modules\Psychology\Services\SocialMemoryService;
use App\Modules\Psychology\ValueObjects\SocialRelation;
use Tests\TestCase;

class SocialMemoryTest extends TestCase
{
    private SocialMemoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SocialMemoryService();
    }

    public function test_record_interaction_creates_or_updates_relation(): void
    {
        $relations = [];
        // First interaction
        $relations = $this->service->recordInteraction($relations, targetId: 2, trustDelta: 0.5, fearDelta: 0.1, dominanceDelta: 0.0, intimacyDelta: 0.2, currentTick: 1);

        $this->assertArrayHasKey(2, $relations);
        $this->assertEquals(0.5, $relations[2]->trust);

        // Second interaction at later tick
        $relations = $this->service->recordInteraction($relations, targetId: 2, trustDelta: -0.2, fearDelta: 0.0, dominanceDelta: -0.5, intimacyDelta: 0.0, currentTick: 50);

        // Note: trust decays before applyDelta. Decay of 0.5 over 49 ticks.
        // Tỷ lệ decay 0.01/tick ->  1 - (1-0.01)^49 ~ 0.38
        // Trust còn ~0.5 * 0.62 = 0.311. Sau đó -0.2 = ~0.11
        $this->assertLessThan(0.4, $relations[2]->trust);
        $this->assertEquals(-0.5, $relations[2]->dominancePerceived);
    }

    public function test_decay_all_reduces_intensity_over_time(): void
    {
        $relations = [
            2 => new SocialRelation(2, 0.8, 0.5, 0.0, 0.9, 100) // tick 100
        ];

        // 50 ticks passed
        $decayed = $this->service->decayAll($relations, 150);

        $this->assertArrayHasKey(2, $decayed);
        $this->assertLessThan(0.8, $decayed[2]->trust);
        $this->assertLessThan(0.9, $decayed[2]->intimacy);
    }

    public function test_prunes_relations_exceeding_dunbar_number_keeping_highest_intensity(): void
    {
        $relations = [];
        // Create 20 relations
        for ($i = 1; $i <= 20; $i++) {
            $relations[$i] = new SocialRelation($i, trust: $i / 20.0, fear: 0, dominancePerceived: 0, intimacy: 0, lastInteractionTick: 1);
        }

        // Trigger prune by adding one more
        $relations = $this->service->recordInteraction($relations, targetId: 99, trustDelta: 1.0, fearDelta: 0.0, dominanceDelta: 0.0, intimacyDelta: 0.0, currentTick: 1);

        // Max is 15.
        $this->assertCount(15, $relations);
        
        // Target 99 has intensity 1.0 (max), must exist
        $this->assertArrayHasKey(99, $relations);
        // The ones with lowest intensity (i=1,2,3...) should be pruned
        $this->assertArrayNotHasKey(1, $relations);
    }

    public function test_prunes_relations_with_intensity_too_low(): void
    {
        $relations = [
            1 => new SocialRelation(1, 0.01, 0.01, 0.01, 0.01, 1),
            2 => new SocialRelation(2, 0.8, 0.0, 0.0, 0.0, 1)
        ];

        $pruned = $this->service->decayAll($relations, 1);
        
        $this->assertArrayNotHasKey(1, $pruned, 'Intensity < 0.05 should be pruned');
        $this->assertArrayHasKey(2, $pruned, 'High intensity must be kept');
    }
}
