<?php

declare(strict_types=1);

namespace Tests\Unit\Simulation;

use App\Modules\Simulation\Core\Domain\EngineResult;
use Tests\TestCase;

class EngineResultTest extends TestCase
{
    public function test_empty_result_has_no_data(): void
    {
        $result = EngineResult::empty();

        $this->assertEmpty($result->events);
        $this->assertEmpty($result->stateChanges);
        $this->assertEmpty($result->metrics);
        $this->assertFalse($result->skipped);
        $this->assertSame('', $result->skipReason);
    }

    public function test_skipped_result(): void
    {
        $result = EngineResult::skipped('No actors in zone');

        $this->assertTrue($result->skipped);
        $this->assertSame('No actors in zone', $result->skipReason);
        $this->assertEmpty($result->events);
        $this->assertEmpty($result->stateChanges);
    }

    public function test_from_effects(): void
    {
        $effects = [new \stdClass()];
        $events = [['type' => 'test']];
        $metrics = ['duration_ms' => 42.0];

        $result = EngineResult::fromEffects($effects, $events, $metrics);

        $this->assertCount(1, $result->stateChanges);
        $this->assertCount(1, $result->events);
        $this->assertSame(42.0, $result->metrics['duration_ms']);
    }

    public function test_add_event(): void
    {
        $result = EngineResult::empty();
        $result->addEvent(['type' => 'boom']);

        $this->assertCount(1, $result->events);
    }

    public function test_link_event(): void
    {
        $result = EngineResult::empty();
        $result->linkEvent('war', 99);

        $this->assertSame(99, $result->causalLinks['war']);
    }

    public function test_get_duration_ms(): void
    {
        $result = new EngineResult(metrics: ['duration_ms' => 15.5]);

        $this->assertSame(15.5, $result->getDurationMs());
    }

    public function test_get_entities_affected_from_metrics(): void
    {
        $result = new EngineResult(metrics: ['entities_affected' => 42]);

        $this->assertSame(42, $result->getEntitiesAffected());
    }

    public function test_get_entities_affected_fallback_to_state_changes_count(): void
    {
        $result = new EngineResult(stateChanges: [new \stdClass(), new \stdClass()]);

        $this->assertSame(2, $result->getEntitiesAffected());
    }
}
