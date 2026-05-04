<?php

namespace Tests\Unit\Psychology;

use App\Modules\Psychology\Services\ReputationResolver;
use App\Modules\Psychology\ValueObjects\SocialRelation;
use Tests\TestCase;

class ReputationTest extends TestCase
{
    private ReputationResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new ReputationResolver();
    }

    public function test_resolves_hero_reputation_correctly(): void
    {
        // 3 people trust the target (id = 99)
        $relations = [
            1 => [99 => new SocialRelation(99, trust: 0.8, fear: 0.0, dominancePerceived: -0.2, intimacy: 0.8, lastInteractionTick: 1)],
            2 => [99 => new SocialRelation(99, trust: 0.9, fear: 0.0, dominancePerceived: -0.1, intimacy: 0.6, lastInteractionTick: 1)],
            3 => [99 => new SocialRelation(99, trust: 0.7, fear: 0.0, dominancePerceived: 0.0, intimacy: 0.7, lastInteractionTick: 1)],
        ];

        $reputation = $this->resolver->resolveReputation($relations, 99);

        // Average trust = 0.8, Average intimacy = 0.7 -> Hero label
        $this->assertEquals('Hero', $reputation['label']);
        $this->assertEqualsWithDelta(0.8, $reputation['trust_score'], 0.0001);
    }

    public function test_resolves_tyrant_reputation_correctly(): void
    {
        // 2 people fear the target
        $relations = [
            1 => [99 => new SocialRelation(99, trust: -0.5, fear: 0.9, dominancePerceived: -0.8, intimacy: 0.0, lastInteractionTick: 1)],
            2 => [99 => new SocialRelation(99, trust: -0.7, fear: 0.8, dominancePerceived: -0.9, intimacy: 0.0, lastInteractionTick: 1)],
        ];

        $reputation = $this->resolver->resolveReputation($relations, 99);

        // Average fear = 0.85, dominance = -0.85 (observer feels dominated) -> Tyrant
        $this->assertEquals('Tyrant', $reputation['label']);
        $this->assertLessThan(-0.5, $reputation['dominance_score']);
    }

    public function test_resolves_outcast_reputation_correctly(): void
    {
        // 2 people distrust the target but don't fear them
        $relations = [
            1 => [99 => new SocialRelation(99, trust: -0.8, fear: 0.1, dominancePerceived: 0.2, intimacy: 0.0, lastInteractionTick: 1)],
            2 => [99 => new SocialRelation(99, trust: -0.7, fear: 0.0, dominancePerceived: 0.5, intimacy: 0.0, lastInteractionTick: 1)],
        ];

        $reputation = $this->resolver->resolveReputation($relations, 99);

        $this->assertEquals('Outcast', $reputation['label']);
    }

    public function test_unknown_actor_has_neutral_reputation(): void
    {
        $relations = [
            1 => [88 => new SocialRelation(88, trust: 0.8, fear: 0.0, dominancePerceived: -0.2, intimacy: 0.8, lastInteractionTick: 1)],
        ];

        // Target 99 does not exist in any observer's relations
        $reputation = $this->resolver->resolveReputation($relations, 99);

        $this->assertEquals('Unknown', $reputation['label']);
        $this->assertEquals(0.0, $reputation['trust_score']);
    }
}
