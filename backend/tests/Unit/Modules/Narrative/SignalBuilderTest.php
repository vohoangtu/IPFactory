<?php

namespace Tests\Unit\Modules\Narrative;

use App\Modules\Narrative\Dto\NarrativeMeaning;
use App\Modules\Narrative\Services\SignalBuilder;
use Tests\TestCase;

class SignalBuilderTest extends TestCase
{
    private SignalBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new SignalBuilder();
    }

    public function test_high_tension_increases_entropy()
    {
        $meaning = new NarrativeMeaning(
            summary: "Test",
            tension: "high",
            direction: "stagnation"
        );

        $signal = $this->builder->build($meaning);

        $this->assertEquals(0.02, $signal->entropyDelta);
        $this->assertEquals(-0.005, $signal->stabilityDelta);
    }

    public function test_growth_direction_increases_stability()
    {
        $meaning = new NarrativeMeaning(
            summary: "Test",
            tension: "low",
            direction: "growth"
        );

        $signal = $this->builder->build($meaning);

        $this->assertEquals(-0.005, $signal->entropyDelta);
        $this->assertEquals(0.015, $signal->stabilityDelta);
    }

    public function test_collapse_decreases_stability()
    {
        $meaning = new NarrativeMeaning(
            summary: "Test",
            tension: "medium",
            direction: "collapse"
        );

        $signal = $this->builder->build($meaning);

        $this->assertEquals(0.005, $signal->entropyDelta);
        $this->assertEquals(-0.02, $signal->stabilityDelta);
    }
}
