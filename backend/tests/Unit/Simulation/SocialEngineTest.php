<?php

declare(strict_types=1);

namespace Tests\Unit\Simulation;

use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Engines\Social\FinanceEngine;
use App\Modules\Simulation\Core\Engines\Social\ProductionChainEngine;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Tests\TestCase;

class SocialEngineTest extends TestCase
{
    // --------------------------------------------------
    // FinanceEngine
    // --------------------------------------------------

    public function test_finance_engine_positive_net_produces_credit(): void
    {
        $state = $this->makeStateWithZones([
            ['economy_surplus' => 100, 'economy_consumption' => 30],
        ]);
        $ctx = new TickContext(1, 10, 42);

        $result = (new FinanceEngine())->handle($state, $ctx);
        $finance = $result->stateChanges[0]['civilization.finance'];

        $this->assertEquals(70, $finance['zones'][0]['credit']);
        $this->assertEquals(0, $finance['zones'][0]['debt']);
        $this->assertEquals(70, $finance['total_credit']);
        $this->assertEquals(0, $finance['total_debt']);
    }

    public function test_finance_engine_negative_net_produces_debt(): void
    {
        $state = $this->makeStateWithZones([
            ['economy_surplus' => 20, 'economy_consumption' => 80],
        ]);
        $ctx = new TickContext(1, 10, 42);

        $result = (new FinanceEngine())->handle($state, $ctx);
        $finance = $result->stateChanges[0]['civilization.finance'];

        $this->assertEquals(0, $finance['zones'][0]['credit']);
        $this->assertEquals(60, $finance['zones'][0]['debt']);
    }

    public function test_finance_engine_empty_zones_returns_empty(): void
    {
        $state = new WorldState(['universe_id' => 1], []);
        $ctx = new TickContext(1, 10, 42);

        $result = (new FinanceEngine())->handle($state, $ctx);

        $this->assertEmpty($result->stateChanges);
    }

    // --------------------------------------------------
    // ProductionChainEngine
    // --------------------------------------------------

    public function test_production_engine_no_material_bonus(): void
    {
        $state = $this->makeStateWithZones([
            ['economy_surplus' => 100, 'material_bonus_count' => 0],
            ['economy_surplus' => 20, 'material_bonus_count' => 0],
        ]);
        $ctx = new TickContext(1, 10, 42);

        $result = (new ProductionChainEngine())->handle($state, $ctx);
        $production = $result->stateChanges[0]['civilization.production'];

        $this->assertEquals(1.0, $production['material_bonus_multiplier']);
        $this->assertEquals(50, $production['zones'][0]['industrial_output']); // 100 * 1.0 * 0.5
        $this->assertEquals(10, $production['zones'][1]['industrial_output']); // 20 * 1.0 * 0.5
        $this->assertEquals(60, $production['total_industrial_output']);
    }

    public function test_production_engine_with_material_bonus(): void
    {
        $state = $this->makeStateWithZones([
            ['economy_surplus' => 100, 'material_bonus_count' => 3],
            ['economy_surplus' => 40, 'material_bonus_count' => 2],
        ]);
        $ctx = new TickContext(1, 10, 42);

        $result = (new ProductionChainEngine())->handle($state, $ctx);
        $production = $result->stateChanges[0]['civilization.production'];

        // Total material_bonus_count = 3 + 2 = 5 → multiplier = 1.0 + (5 * 0.1) = 1.5
        $this->assertEquals(1.5, $production['material_bonus_multiplier']);
        $this->assertEquals(75, $production['zones'][0]['industrial_output']); // 100 * 1.5 * 0.5
        $this->assertEquals(30, $production['zones'][1]['industrial_output']); // 40 * 1.5 * 0.5
        $this->assertEquals(105, $production['total_industrial_output']);
    }

    public function test_production_engine_empty_zones_returns_empty(): void
    {
        $state = new WorldState(['universe_id' => 1], []);
        $ctx = new TickContext(1, 10, 42);

        $result = (new ProductionChainEngine())->handle($state, $ctx);

        $this->assertEmpty($result->stateChanges);
    }

    // --------------------------------------------------
    // Helpers
    // --------------------------------------------------

    private function makeStateWithZones(array $zoneStates): WorldState
    {
        $zones = [];
        foreach ($zoneStates as $index => $zoneState) {
            $zones[$index] = ['state' => $zoneState];
        }

        $state = new WorldState(['universe_id' => 1], []);
        $state->set('zones', $zones);

        return $state;
    }
}
