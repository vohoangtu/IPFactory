<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class WorldosRouteAuthTest extends TestCase
{
    public function test_generate_chronicle_requires_auth(): void
    {
        $this->postJson('/api/worldos/universes/1/generate-chronicle')
            ->assertStatus(401);
    }

    public function test_test_weave_route_is_removed(): void
    {
        $this->postJson('/api/worldos/test-weave/1')
            ->assertStatus(404);
    }

    public function test_history_timeline_route_is_removed(): void
    {
        $this->getJson('/api/worldos/universes/1/history-timeline')->assertStatus(404);
    }

    public function test_analytics_ticks_route_is_removed(): void
    {
        $this->getJson('/api/worldos/analytics/ticks')->assertStatus(404);
    }

    public function test_worlds_pulse_route_is_removed(): void
    {
        $this->postJson('/api/worldos/worlds/1/pulse')->assertStatus(404);
    }
}
