<?php

declare(strict_types=1);

namespace Tests\Unit\Simulation;

use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Engines\Physics\ClimateEngine;
use App\Modules\Simulation\Core\Engines\Physics\GeologicalEngine;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Tests\TestCase;

/**
 * Smoke tests for Environment/Physics engines.
 */
class EnvironmentEngineSmokeTest extends TestCase
{
    // --------------------------------------------------
    // ClimateEngine
    // --------------------------------------------------

    public function test_climate_engine_computes_temperature_and_biome(): void
    {
        config(['worldos.planetary_climate.tick_interval' => 1]);

        $engine = new ClimateEngine();
        $state = $this->makeZoneState();
        $ctx = new TickContext(1, 1, 42);

        $result = $engine->handle($state, $ctx);

        $this->assertInstanceOf(EngineResult::class, $result);
        $this->assertNotEmpty($result->stateChanges);

        $zones = $result->stateChanges[0]['zones'];
        $this->assertNotEmpty($zones);

        // Each zone should have temperature, rainfall, biome
        foreach ($zones as $zone) {
            $s = $zone['state'];
            $this->assertArrayHasKey('temperature', $s);
            $this->assertArrayHasKey('rainfall', $s);
            $this->assertArrayHasKey('biome', $s);
            $this->assertGreaterThanOrEqual(0, $s['temperature']);
            $this->assertLessThanOrEqual(1, $s['temperature']);
        }
    }

    public function test_climate_engine_skips_non_interval_tick(): void
    {
        config(['worldos.planetary_climate.tick_interval' => 10]);

        $engine = new ClimateEngine();
        $state = $this->makeZoneState();
        $ctx = new TickContext(1, 3, 42);

        $result = $engine->handle($state, $ctx);
        $this->assertEmpty($result->stateChanges);
    }

    public function test_climate_biome_varies_with_conditions(): void
    {
        config(['worldos.planetary_climate.tick_interval' => 1]);

        $engine = new ClimateEngine();

        // Zone with high elevation → should have lower temperature
        $state = new WorldState(['universe_id' => 1], []);
        $state->set('zones', [
            ['id' => 0, 'state' => ['elevation' => 0.9]],
            ['id' => 1, 'state' => ['elevation' => 0.1]],
        ]);
        $ctx = new TickContext(1, 1, 42);

        $result = $engine->handle($state, $ctx);
        $zones = $result->stateChanges[0]['zones'];

        // High elevation zone should have lower temperature than low elevation
        $this->assertLessThan(
            $zones[1]['state']['temperature'],
            $zones[0]['state']['temperature'],
            'High elevation zone should be colder'
        );
    }

    // --------------------------------------------------
    // GeologicalEngine
    // --------------------------------------------------

    public function test_geological_engine_computes_elevation_and_terrain(): void
    {
        config(['worldos.geological.tick_interval' => 1]);

        $engine = new GeologicalEngine();
        $state = $this->makeZoneState();
        $ctx = new TickContext(1, 1, 42);

        $result = $engine->handle($state, $ctx);

        $this->assertInstanceOf(EngineResult::class, $result);
        $this->assertNotEmpty($result->stateChanges);

        $zones = $result->stateChanges[0]['zones'];
        foreach ($zones as $zone) {
            $s = $zone['state'];
            $this->assertArrayHasKey('elevation', $s);
            $this->assertArrayHasKey('terrain_type', $s);
            $this->assertArrayHasKey('mineral_richness', $s);
            $this->assertGreaterThanOrEqual(0, $s['elevation']);
            $this->assertLessThanOrEqual(1, $s['elevation']);
            $this->assertContains($s['terrain_type'], [
                'ocean', 'plains', 'hills', 'highlands', 'mountains', 'peaks',
            ]);
        }
    }

    public function test_geological_engine_skips_non_interval_tick(): void
    {
        config(['worldos.geological.tick_interval' => 10]);

        $engine = new GeologicalEngine();
        $state = $this->makeZoneState();
        $ctx = new TickContext(1, 3, 42);

        $result = $engine->handle($state, $ctx);
        $this->assertEmpty($result->stateChanges);
    }

    public function test_geological_engine_is_deterministic(): void
    {
        config(['worldos.geological.tick_interval' => 1]);

        $engine = new GeologicalEngine();
        $state1 = $this->makeZoneState();
        $state2 = $this->makeZoneState();
        $ctx = new TickContext(1, 1, 42); // same seed

        $result1 = $engine->handle($state1, $ctx);
        $result2 = $engine->handle($state2, $ctx);

        // Same seed → same output
        $this->assertEquals(
            $result1->stateChanges[0]['zones'][0]['state']['elevation'],
            $result2->stateChanges[0]['zones'][0]['state']['elevation'],
            'Same seed should produce deterministic elevation'
        );
    }

    // --------------------------------------------------
    // Helpers
    // --------------------------------------------------

    private function makeZoneState(): WorldState
    {
        $state = new WorldState(['universe_id' => 1], []);
        $state->set('zones', [
            ['id' => 0, 'state' => ['elevation' => 0.5]],
            ['id' => 1, 'state' => ['elevation' => 0.3]],
            ['id' => 2, 'state' => ['elevation' => 0.7]],
            ['id' => 3, 'state' => ['elevation' => 0.1]],
        ]);

        return $state;
    }
}
