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

/**
 * Integration test: WorldKernel + PhaseRegistry full tick cycle.
 *
 * Registers engines across all 5 phases and verifies they all execute
 * in the correct order during a simulated tick.
 */
class WorldKernelIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_full_tick_executes_all_five_phases_in_order(): void
    {
        $executionLog = [];
        $registry = new PhaseRegistry();

        // Register one engine per phase to track execution order
        foreach (SimulationPhase::cases() as $phase) {
            $engineName = 'engine-' . strtolower($phase->name);
            $registry->register($this->makeEngine(
                $engineName,
                $phase,
                function () use (&$executionLog, $engineName) {
                    $executionLog[] = $engineName;

                    return new EngineResult(
                        stateChanges: [[$engineName => 'executed']],
                    );
                },
            ));
        }

        $kernel = $this->makeKernel($registry);
        $state = new WorldState([]);

        // Execute all phases via reflection
        $method = new \ReflectionMethod($kernel, 'executeRegistryPhase');
        $method->setAccessible(true);

        $phaseResults = [];
        foreach (SimulationPhase::cases() as $phase) {
            $phaseResults[$phase->name] = $method->invoke($kernel, $phase, $state, 1);
        }

        // All 5 phases should have executed
        $this->assertCount(5, $executionLog);
        $this->assertSame([
            'engine-environment',
            'engine-life',
            'engine-mind',
            'engine-social',
            'engine-meta',
        ], $executionLog);

        // Each phase result should have 1 engine
        foreach ($phaseResults as $phaseName => $result) {
            $this->assertSame(1, $result->getEngineCount(), "Phase {$phaseName} should have 1 engine");
        }
    }

    public function test_multiple_engines_per_phase_execute_in_priority_order(): void
    {
        $executionLog = [];
        $registry = new PhaseRegistry();

        // 3 engines in Environment, 2 in Social
        $registry->register($this->makeEngine('env-c', SimulationPhase::Environment, function () use (&$executionLog) {
            $executionLog[] = 'env-c';

            return EngineResult::empty();
        }, priority: 30));
        $registry->register($this->makeEngine('env-a', SimulationPhase::Environment, function () use (&$executionLog) {
            $executionLog[] = 'env-a';

            return EngineResult::empty();
        }, priority: 10));
        $registry->register($this->makeEngine('env-b', SimulationPhase::Environment, function () use (&$executionLog) {
            $executionLog[] = 'env-b';

            return EngineResult::empty();
        }, priority: 20));

        $registry->register($this->makeEngine('soc-b', SimulationPhase::Social, function () use (&$executionLog) {
            $executionLog[] = 'soc-b';

            return EngineResult::empty();
        }, priority: 20));
        $registry->register($this->makeEngine('soc-a', SimulationPhase::Social, function () use (&$executionLog) {
            $executionLog[] = 'soc-a';

            return EngineResult::empty();
        }, priority: 10));

        $kernel = $this->makeKernel($registry);
        $state = new WorldState([]);

        $method = new \ReflectionMethod($kernel, 'executeRegistryPhase');
        $method->setAccessible(true);

        // Execute Environment phase
        $method->invoke($kernel, SimulationPhase::Environment, $state, 1);
        // Execute Social phase
        $method->invoke($kernel, SimulationPhase::Social, $state, 1);

        $this->assertSame([
            'env-a', 'env-b', 'env-c',  // Environment: priority 10, 20, 30
            'soc-a', 'soc-b',            // Social: priority 10, 20
        ], $executionLog);
    }

    public function test_engine_failure_does_not_block_other_engines(): void
    {
        $executionLog = [];
        $registry = new PhaseRegistry();

        $registry->register($this->makeEngine('good-1', SimulationPhase::Life, function () use (&$executionLog) {
            $executionLog[] = 'good-1';

            return EngineResult::empty();
        }, priority: 10));

        $registry->register($this->makeEngine('bad', SimulationPhase::Life, function () {
            throw new \RuntimeException('Simulated engine crash');
        }, priority: 20));

        $registry->register($this->makeEngine('good-2', SimulationPhase::Life, function () use (&$executionLog) {
            $executionLog[] = 'good-2';

            return EngineResult::empty();
        }, priority: 30));

        $kernel = $this->makeKernel($registry);
        $state = new WorldState([]);

        $method = new \ReflectionMethod($kernel, 'executeRegistryPhase');
        $method->setAccessible(true);

        $result = $method->invoke($kernel, SimulationPhase::Life, $state, 1);

        // Good engines should have executed despite the failing one
        $this->assertSame(['good-1', 'good-2'], $executionLog);
        $this->assertContains('bad', $result->getSkippedEngines());
    }

    public function test_state_mutations_accumulate_across_engines(): void
    {
        $registry = new PhaseRegistry();

        // Engine 1: sets a key
        $registry->register($this->makeEngine('setter', SimulationPhase::Environment, function (WorldState $state) {
            $state->set('test_key', 'initial_value');

            return new EngineResult(stateChanges: [['test_key' => 'initial_value']]);
        }, priority: 10));

        // Engine 2: reads and modifies the key
        $registry->register($this->makeEngine('modifier', SimulationPhase::Environment, function (WorldState $state) {
            $val = $state->get('test_key', 'missing');

            return new EngineResult(stateChanges: [['test_key_read' => $val]]);
        }, priority: 20));

        $kernel = $this->makeKernel($registry);
        $state = new WorldState([]);

        $method = new \ReflectionMethod($kernel, 'executeRegistryPhase');
        $method->setAccessible(true);

        $result = $method->invoke($kernel, SimulationPhase::Environment, $state, 1);

        // State set by engine 1 should be readable by engine 2
        $this->assertSame('initial_value', $state->get('test_key'));
        $this->assertSame(2, $result->getEngineCount());
    }

    // --------------------------------------------------
    // Helpers
    // --------------------------------------------------

    private function makeKernel(?PhaseRegistry $registry = null): WorldKernel
    {
        $stateManager = Mockery::mock(StateManager::class);

        return new WorldKernel($stateManager, $registry);
    }

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

            public function name(): string
            {
                return $this->engineName;
            }

            public function phase(): SimulationPhase
            {
                return $this->enginePhase;
            }

            public function priority(): int
            {
                return $this->enginePriority;
            }

            public function execute(WorldState $state, TickContext $ctx): EngineResult
            {
                return ($this->callback)($state, $ctx);
            }
        };
    }
}
