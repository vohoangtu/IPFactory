<?php

declare(strict_types=1);

namespace Tests\Unit\Simulation;

use App\Modules\Simulation\Core\Domain\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\StateManager;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Core\Runtime\WorldKernel;
use App\Modules\Simulation\Engines\AbstractWorldOSEngine;
use App\Modules\Simulation\Enums\SimulationPhase;
use App\Modules\Simulation\Services\Kernel\PhaseRegistry;
use Mockery;
use Tests\TestCase;

class WorldKernelV2Test extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // --------------------------------------------------
    // Helper: create a minimal WorldKernel with PhaseRegistry
    // --------------------------------------------------

    private function makeKernel(?PhaseRegistry $registry = null): WorldKernel
    {
        $stateManager = Mockery::mock(StateManager::class);

        return new WorldKernel($stateManager, $registry);
    }

    private function makeState(): WorldState
    {
        $state = Mockery::mock(WorldState::class)->makePartial();
        $state->shouldReceive('syncAgentsToZones')->andReturnNull();

        return $state;
    }

    // --------------------------------------------------
    // v2 PhaseRegistry engine execution
    // --------------------------------------------------

    public function test_execute_registry_phase_runs_engines(): void
    {
        $registry = new PhaseRegistry();

        $executed = [];

        $engine = $this->makeEngine('test-env', SimulationPhase::Environment, function () use (&$executed) {
            $executed[] = 'test-env';
            return new EngineResult(stateChanges: [['foo' => 'bar']]);
        });

        $registry->register($engine);

        $kernel = $this->makeKernel($registry);

        // Access protected method via reflection
        $method = new \ReflectionMethod($kernel, 'executeRegistryPhase');
        $method->setAccessible(true);

        $state = new WorldState([]);
        $result = $method->invoke($kernel, SimulationPhase::Environment, $state, 1);

        $this->assertSame(['test-env'], $executed);
        $this->assertSame(1, $result->getEngineCount());
    }

    public function test_engine_failure_produces_skipped_result(): void
    {
        $registry = new PhaseRegistry();

        $engine = $this->makeEngine('failing-engine', SimulationPhase::Life, function () {
            throw new \RuntimeException('Engine crashed');
        });

        $registry->register($engine);

        $kernel = $this->makeKernel($registry);

        $method = new \ReflectionMethod($kernel, 'executeRegistryPhase');
        $method->setAccessible(true);

        $state = new WorldState([]);
        $result = $method->invoke($kernel, SimulationPhase::Life, $state, 1);

        $this->assertSame(1, $result->getEngineCount());
        $this->assertSame(['failing-engine'], $result->getSkippedEngines());
    }

    public function test_phase_with_no_engines_returns_empty_result(): void
    {
        $registry = new PhaseRegistry();

        $kernel = $this->makeKernel($registry);

        $method = new \ReflectionMethod($kernel, 'executeRegistryPhase');
        $method->setAccessible(true);

        $state = new WorldState([]);
        $result = $method->invoke($kernel, SimulationPhase::Meta, $state, 1);

        $this->assertSame(0, $result->getEngineCount());
    }

    public function test_engines_execute_in_priority_order(): void
    {
        $registry = new PhaseRegistry();
        $order = [];

        $registry->register($this->makeEngine('third', SimulationPhase::Social, function () use (&$order) {
            $order[] = 'third';
            return EngineResult::empty();
        }, priority: 30));

        $registry->register($this->makeEngine('first', SimulationPhase::Social, function () use (&$order) {
            $order[] = 'first';
            return EngineResult::empty();
        }, priority: 10));

        $registry->register($this->makeEngine('second', SimulationPhase::Social, function () use (&$order) {
            $order[] = 'second';
            return EngineResult::empty();
        }, priority: 20));

        $kernel = $this->makeKernel($registry);

        $method = new \ReflectionMethod($kernel, 'executeRegistryPhase');
        $method->setAccessible(true);

        $state = new WorldState([]);
        $method->invoke($kernel, SimulationPhase::Social, $state, 1);

        $this->assertSame(['first', 'second', 'third'], $order);
    }

    public function test_skipped_engine_tracked_in_result(): void
    {
        $registry = new PhaseRegistry();

        $registry->register($this->makeEngine('skippable', SimulationPhase::Mind, function () {
            return EngineResult::skipped('No work');
        }));

        $kernel = $this->makeKernel($registry);

        $method = new \ReflectionMethod($kernel, 'executeRegistryPhase');
        $method->setAccessible(true);

        $state = new WorldState([]);
        $result = $method->invoke($kernel, SimulationPhase::Mind, $state, 1);

        $this->assertSame(['skippable'], $result->getSkippedEngines());
    }

    // --------------------------------------------------
    // Helper: create engine with custom execute callback
    // --------------------------------------------------

    private function makeEngine(
        string $name,
        SimulationPhase $phase,
        \Closure $callback,
        int $priority = 0,
    ): AbstractWorldOSEngine {
        return new class($name, $phase, $callback, $priority) extends AbstractWorldOSEngine {
            public function __construct(
                private readonly string $engineName,
                private readonly SimulationPhase $enginePhase,
                private readonly \Closure $callback,
                private readonly int $enginePriority,
            ) {
            }

            public function name(): string { return $this->engineName; }
            public function phase(): SimulationPhase { return $this->enginePhase; }
            public function priority(): int { return $this->enginePriority; }

            public function execute(WorldState $state, TickContext $ctx): EngineResult
            {
                return ($this->callback)($state, $ctx);
            }
        };
    }
}
