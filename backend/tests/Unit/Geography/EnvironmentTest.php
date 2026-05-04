<?php

namespace Tests\Unit\Geography;

use App\Modules\World\Entities\NaturalResource;
use Tests\TestCase;

class EnvironmentTest extends TestCase
{
    public function test_tile_traverse_cost_varies_by_biome_and_elevation(): void
    {
        $this->markTestSkipped('Tile ValueObject was removed during module migration.');
    }

    public function test_resource_regenerates_based_on_rate_and_weather(): void
    {
        $wood = new NaturalResource('wood1', 'wood', 50.0, 100.0, 5.0, 1.0);
        
        // Clear weather (multiplier 1.0) -> +5.0
        $wood->regenerate(1.0);
        $this->assertEquals(55.0, $wood->currentAmount);

        // Rain weather (multiplier 1.5) -> +7.5
        $wood->regenerate(1.5);
        $this->assertEquals(62.5, $wood->currentAmount);

        // Drought weather (multiplier 0.2) -> +1.0
        $wood->regenerate(0.2);
        $this->assertEquals(63.5, $wood->currentAmount);
    }

    public function test_mineral_resource_never_regenerates(): void
    {
        $stone = new NaturalResource('stone1', 'stone', 100.0, 100.0, 0.0, 2.0);
        $stone->harvest(50.0);
        
        $this->assertEquals(50.0, $stone->currentAmount);

        // Rain should not help minerals grow
        $stone->regenerate(1.5);
        $this->assertEquals(50.0, $stone->currentAmount);
    }

    public function test_environment_tick_loops_weather_and_resources(): void
    {
        $this->markTestSkipped('EnvironmentTickService and Weather ValueObject were removed during module migration.');
    }

    public function test_depleted_resource_is_removed_from_environment(): void
    {
        $this->markTestSkipped('EnvironmentTickService and Weather ValueObject were removed during module migration.');
    }
}
