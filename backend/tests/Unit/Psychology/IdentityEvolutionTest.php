<?php

namespace Tests\Unit\Psychology;

use App\Modules\Psychology\Services\IdentityEvolutionService;
use App\Modules\Psychology\ValueObjects\IdentityState;
use Tests\TestCase;

class IdentityEvolutionTest extends TestCase
{
    private IdentityEvolutionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new IdentityEvolutionService();
    }

    public function test_matches_archetype_increases_alignment_and_worth(): void
    {
        $identity = IdentityState::baseline(); // role_conflict 0.0, worth 0.5, alignment 1.0

        // Cooperate is aligned with VillageElder (+0.8 expected internal proxy)
        $evolved = $this->service->evaluateBehavior($identity, 'cooperate', [], 'VillageElder');

        // Should decrease conflict, increase worth, increase alignment
        $this->assertEquals(0.0, $evolved->roleConflict); // clamped to 0
        $this->assertGreaterThan(0.5, $evolved->selfWorth);
        $this->assertEquals(1.0, $evolved->archetypeAlignment); // clamped to 1
    }

    public function test_opposes_archetype_increases_conflict(): void
    {
        $identity = new IdentityState(0.5, 0.1, 0.9);

        // Attack opposes VillageElder (-0.8 expected)
        $evolved = $this->service->evaluateBehavior($identity, 'attack', [], 'VillageElder');

        $this->assertGreaterThan(0.1, $evolved->roleConflict, 'Incompatible behavior should increase conflict');
        $this->assertLessThan(0.9, $evolved->archetypeAlignment, 'Incompatible behavior should decrease alignment');
        
        // However, 'attack' is an active action -> slightly increases worth, 
        // BUT incompatible archetype decreases worth. 
        // +0.01 (active) - 0.01 (incompatible) = 0 delta.
        $this->assertEquals(0.5, $evolved->selfWorth);
    }

    public function test_submissive_behavior_decreases_self_worth(): void
    {
        $identity = new IdentityState(0.5, 0.0, 1.0);

        // passive reduces worth (-0.02)
        // Also passive for VillageElder is slightly compatible (+0.5) 
        // -> increases worth (+0.02)
        // Net worth delta is 0 for VillageElder doing passive. Let's test non-archetype.
        $evolved = $this->service->evaluateBehavior($identity, 'withdraw', [], 'UnknownArchetype');

        // Unknown archetype -> no compatibility match -> baseline conflict decay (-0.01) -> but worth drops due to 'withdraw' (-0.02)
        $this->assertLessThan(0.5, $evolved->selfWorth, 'Withdrawing should decrease self worth');
    }

    public function test_crisis_detection_works(): void
    {
        $crisisIdentity = new IdentityState(selfWorth: 0.1, roleConflict: 0.9, archetypeAlignment: 0.1);
        $this->assertTrue($crisisIdentity->isCrisis());

        $fineIdentity = new IdentityState(selfWorth: 0.6, roleConflict: 0.2, archetypeAlignment: 0.8);
        $this->assertFalse($fineIdentity->isCrisis());
    }
}
