<?php

namespace Tests\Unit\Psychology;

use App\Modules\Psychology\Services\ConflictDetector;
use App\Modules\Psychology\Services\ConflictResolver;
use App\Modules\Psychology\ValueObjects\Impulse;
use Tests\TestCase;

class ConflictEngineTest extends TestCase
{
    private ConflictDetector $detector;
    private ConflictResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new ConflictDetector();
        $this->resolver = new ConflictResolver();
    }

    public function test_approach_and_avoid_are_detected_as_conflict(): void
    {
        $impulses = [
            new Impulse(Impulse::TYPE_DESIRE, Impulse::ACTION_APPROACH, 0.7, 0.6),
            new Impulse(Impulse::TYPE_FEAR,   Impulse::ACTION_AVOID,    0.6, 0.8),
        ];

        $conflicts = $this->detector->detect($impulses);

        $this->assertNotEmpty($conflicts, 'approach vs avoid should be detected as conflict');
        $this->assertGreaterThan(0, $conflicts[0]->tension);
    }

    public function test_same_direction_impulses_do_not_conflict(): void
    {
        $impulses = [
            new Impulse(Impulse::TYPE_DESIRE, Impulse::ACTION_APPROACH, 0.7, 0.6),
            new Impulse(Impulse::TYPE_DUTY,   Impulse::ACTION_APPROACH, 0.5, 0.5),
        ];

        $conflicts = $this->detector->detect($impulses);

        $this->assertEmpty($conflicts, 'Same-direction impulses should not conflict');
    }

    public function test_stronger_impulse_suppresses_weaker(): void
    {
        $strongApproach = new Impulse(Impulse::TYPE_DESIRE, Impulse::ACTION_APPROACH, 0.8, 0.6);
        $weakAvoid      = new Impulse(Impulse::TYPE_FEAR,   Impulse::ACTION_AVOID,    0.3, 0.8);

        $impulses  = [$strongApproach, $weakAvoid];
        $conflicts = $this->detector->detect($impulses);

        $originalWeakIntensity = $weakAvoid->intensity;
        $this->resolver->resolve($impulses, $conflicts);

        // Weaker impulse should be suppressed (not removed)
        $this->assertLessThan($originalWeakIntensity, $weakAvoid->intensity,
            'Weaker impulse should be suppressed');
        $this->assertGreaterThan(0, $weakAvoid->intensity,
            'Suppressed impulse should not be zero (leakage possible)');
        // Stronger impulse should remain unchanged
        $this->assertEquals(0.8, $strongApproach->intensity,
            'Stronger impulse should not be suppressed');
    }

    public function test_equal_strength_conflict_causes_ambivalence(): void
    {
        $a = new Impulse(Impulse::TYPE_DESIRE, Impulse::ACTION_APPROACH, 0.6, 0.6);
        $b = new Impulse(Impulse::TYPE_FEAR,   Impulse::ACTION_AVOID,    0.6, 0.8);

        $conflicts = $this->detector->detect([$a, $b]);
        $this->resolver->resolve([$a, $b], $conflicts);

        // Both should be partially suppressed (ambivalence)
        $this->assertLessThan(0.6, $a->intensity, 'Equal conflict: A should be partially suppressed');
        $this->assertLessThan(0.6, $b->intensity, 'Equal conflict: B should be partially suppressed');
    }

    public function test_conflict_generates_stress_delta(): void
    {
        $impulses = [
            new Impulse(Impulse::TYPE_DESIRE, Impulse::ACTION_APPROACH, 0.8, 0.6),
            new Impulse(Impulse::TYPE_FEAR,   Impulse::ACTION_AVOID,    0.7, 0.8),
        ];

        $conflicts = $this->detector->detect($impulses);
        $resolved  = $this->resolver->resolve($impulses, $conflicts);

        $this->assertGreaterThan(0, $resolved['stress_delta'],
            'Internal conflict should generate stress');
    }
}
