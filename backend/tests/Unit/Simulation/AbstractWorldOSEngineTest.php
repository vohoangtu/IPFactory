<?php

declare(strict_types=1);

namespace Tests\Unit\Simulation;

use App\Modules\Simulation\Core\Domain\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Engines\AbstractWorldOSEngine;
use App\Modules\Simulation\Enums\SimulationPhase;
use Tests\TestCase;

class AbstractWorldOSEngineTest extends TestCase
{
    public function test_default_priority_is_zero(): void
    {
        $engine = $this->createConcreteEngine();

        $this->assertSame(0, $engine->priority());
    }

    public function test_default_is_enabled_returns_true(): void
    {
        $engine = $this->createConcreteEngine();

        $this->assertTrue($engine->isEnabled());
    }

    public function test_abstract_methods_are_implemented(): void
    {
        $engine = $this->createConcreteEngine();

        $this->assertSame('test-concrete-engine', $engine->name());
        $this->assertSame(SimulationPhase::Environment, $engine->phase());
    }

    private function createConcreteEngine(): AbstractWorldOSEngine
    {
        return new class extends AbstractWorldOSEngine {
            public function name(): string { return 'test-concrete-engine'; }
            public function phase(): SimulationPhase { return SimulationPhase::Environment; }

            public function execute(WorldState $state, TickContext $ctx): EngineResult
            {
                return EngineResult::empty();
            }
        };
    }
}
