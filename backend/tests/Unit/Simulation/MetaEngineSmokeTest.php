<?php

declare(strict_types=1);

namespace Tests\Unit\Simulation;

use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Engines\EngineResult;
use App\Modules\Simulation\Core\Engines\Meta\InformationDensityEngine;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use Tests\TestCase;

/**
 * Smoke tests for Meta engines.
 *
 * InformationDensityEngine is the primary unit test target
 * (no DB dependencies, pure state computation).
 * DB-dependent Meta engines tested at feature level.
 */
class MetaEngineSmokeTest extends TestCase
{
    // --------------------------------------------------
    // InformationDensityEngine — Bekenstein Bound Model
    // --------------------------------------------------

    public function test_information_density_low_data_mass(): void
    {
        $engine = new InformationDensityEngine();
        $state = new WorldState(['universe_id' => 1], []);
        $state->setCosmic([]);
        $state->setFields(['entropy' => 0.3]);

        // Few data points → low data mass
        $state->set('meta', [
            'active_myths' => [1, 2],
            'meaning_systems' => [1],
            'knowledge_graph' => [1, 2, 3],
        ]);
        $state->set('recentChronicles', [1]);
        $state->set('historical_scars', []);

        $ctx = new TickContext(1, 5, 42);
        $result = $engine->handle($state, $ctx);

        $this->assertInstanceOf(EngineResult::class, $result);

        $cosmic = $state->getCosmic();
        // dataMass = (2*0.02) + (1*0.05) + (3*0.03) + (1*0.01) + (0*0.04) = 0.04 + 0.05 + 0.09 + 0.01 = 0.19
        $this->assertLessThan(0.8, $cosmic['data_mass']);
        $this->assertEquals(0, $cosmic['time_dilation']);
        $this->assertFalse($cosmic['saturation_lock']);

        // Entropy should remain unchanged
        $fields = $state->getFields();
        $this->assertEquals(0.3, $fields['entropy']);
    }

    public function test_information_density_high_data_mass_triggers_dilation(): void
    {
        $engine = new InformationDensityEngine();
        $state = new WorldState(['universe_id' => 1], []);
        $state->setCosmic([]);
        $state->setFields(['entropy' => 0.3]);

        // Many data points → high data mass > 0.8
        $state->set('meta', [
            'active_myths' => array_fill(0, 20, 1),        // 20 * 0.02 = 0.40
            'meaning_systems' => array_fill(0, 10, 1),     // 10 * 0.05 = 0.50
            'knowledge_graph' => array_fill(0, 5, 1),      // 5 * 0.03 = 0.15
        ]);
        // Total: 0.40 + 0.50 + 0.15 = 1.05 (> 0.8)
        $state->set('recentChronicles', []);
        $state->set('historical_scars', []);

        $ctx = new TickContext(1, 5, 42);
        $result = $engine->handle($state, $ctx);

        $cosmic = $state->getCosmic();
        $this->assertGreaterThan(0.8, $cosmic['data_mass']);
        $this->assertGreaterThan(0, $cosmic['time_dilation']);

        // Entropy should have increased
        $fields = $state->getFields();
        $this->assertGreaterThan(0.3, $fields['entropy']);
    }

    public function test_information_density_saturation_lock(): void
    {
        $engine = new InformationDensityEngine();
        $state = new WorldState(['universe_id' => 1], []);
        $state->setCosmic([]);
        $state->setFields(['entropy' => 0.5]);

        // Extreme data → dataMass > 0.95
        $state->set('meta', [
            'active_myths' => array_fill(0, 20, 1),        // 0.40
            'meaning_systems' => array_fill(0, 12, 1),     // 0.60
            'knowledge_graph' => array_fill(0, 5, 1),      // 0.15
        ]);
        // Total: 1.15 → capped to 1.15 (< 1.5), > 0.95
        $state->set('recentChronicles', []);
        $state->set('historical_scars', []);

        $ctx = new TickContext(1, 5, 42);
        $engine->handle($state, $ctx);

        $cosmic = $state->getCosmic();
        $this->assertTrue($cosmic['saturation_lock']);
    }

    public function test_information_density_data_mass_capped_at_1_5(): void
    {
        $engine = new InformationDensityEngine();
        $state = new WorldState(['universe_id' => 1], []);
        $state->setCosmic([]);
        $state->setFields(['entropy' => 0.1]);

        // Extreme overload
        $state->set('meta', [
            'active_myths' => array_fill(0, 100, 1),       // 2.00
            'meaning_systems' => array_fill(0, 50, 1),     // 2.50
            'knowledge_graph' => array_fill(0, 100, 1),    // 3.00
        ]);
        $state->set('recentChronicles', array_fill(0, 100, 1)); // 1.00
        $state->set('historical_scars', array_fill(0, 50, 1));  // 2.00
        // Total: 10.50 → capped at 1.5

        $ctx = new TickContext(1, 5, 42);
        $engine->handle($state, $ctx);

        $cosmic = $state->getCosmic();
        $this->assertEquals(1.5, $cosmic['data_mass']);
    }

    public function test_information_density_empty_state(): void
    {
        $engine = new InformationDensityEngine();
        $state = new WorldState(['universe_id' => 1], []);
        $state->setCosmic([]);
        $state->setFields([]);

        $ctx = new TickContext(1, 5, 42);
        $result = $engine->handle($state, $ctx);

        $this->assertInstanceOf(EngineResult::class, $result);

        $cosmic = $state->getCosmic();
        $this->assertEquals(0, $cosmic['data_mass']);
        $this->assertEquals(0, $cosmic['time_dilation']);
        $this->assertFalse($cosmic['saturation_lock']);
    }
}
