<?php

namespace Tests\Feature;

use App\Simulation\Contracts\SimulationEngine;
use App\Simulation\Domain\EngineResult;
use App\Simulation\Domain\TickContext;
use App\Simulation\EngineRegistry;
use App\Simulation\Runtime\State\WorldState;
use App\Simulation\SimulationKernel;
use Tests\TestCase;

class EngineThrottlingTest extends TestCase
{
    /** @test */
    public function it_skips_low_priority_engines_when_tick_time_exceeds_threshold()
    {
        $registry = app(EngineRegistry::class);

        $criticalSlowEngine = new class implements SimulationEngine {
            public bool $executed = false;
            public function name(): string { return 'CriticalSlow'; }
            public function version(): string { return '1.0'; }
            public function priority(): int { return 1; }
            public function phase(): string { return 'critical'; }
            public function tickRate(): int { return 1; }
            public function isParallelSafe(): bool { return false; }
            public function priorityCategory(): string { return 'CRITICAL'; }
            public function handle(WorldState $state, TickContext $ctx): EngineResult {
                $this->executed = true;
                usleep(850000); // 850ms, deliberately breaking the 800ms STOCHASTIC threshold and 500ms COSMETIC threshold.
                return EngineResult::empty();
            }
        };

        $cosmeticEngine = new class implements SimulationEngine {
            public bool $executed = false;
            public float $elapsedWhenRun = 0.0;
            public function name(): string { return 'CosmeticFast'; }
            public function version(): string { return '1.0'; }
            public function priority(): int { return 2; }
            public function phase(): string { return 'cosmetic'; }
            public function tickRate(): int { return 1; }
            public function isParallelSafe(): bool { return false; }
            public function priorityCategory(): string { return 'COSMETIC'; }
            public function handle(WorldState $state, TickContext $ctx): EngineResult {
                $this->elapsedWhenRun = microtime(true);
                $this->executed = true;
                return EngineResult::empty();
            }
        };

        $stochasticEngine = new class implements SimulationEngine {
            public bool $executed = false;
            public float $elapsedWhenRun = 0.0;
            public function name(): string { return 'StochasticFast'; }
            public function version(): string { return '1.0'; }
            public function priority(): int { return 3; }
            public function phase(): string { return 'stochastic'; }
            public function tickRate(): int { return 1; }
            public function isParallelSafe(): bool { return false; }
            public function priorityCategory(): string { return 'STOCHASTIC'; }
            public function handle(WorldState $state, TickContext $ctx): EngineResult {
                $this->elapsedWhenRun = microtime(true);
                $this->executed = true;
                return EngineResult::empty();
            }
        };

        $criticalFastEngine = new class implements SimulationEngine {
            public bool $executed = false;
            public function name(): string { return 'CriticalFast'; }
            public function version(): string { return '1.0'; }
            public function priority(): int { return 4; }
            public function phase(): string { return 'critical_late'; }
            public function tickRate(): int { return 1; }
            public function isParallelSafe(): bool { return false; }
            public function priorityCategory(): string { return 'CRITICAL'; }
            public function handle(WorldState $state, TickContext $ctx): EngineResult {
                $this->executed = true;
                return EngineResult::empty();
            }
        };

        // We register them dynamically for the test environment
        $registry->register($criticalSlowEngine);
        $registry->register($cosmeticEngine);
        $registry->register($stochasticEngine);
        $registry->register($criticalFastEngine);

        $kernel = app(SimulationKernel::class);
        $state = new WorldState(['tick' => 1]);
        $ctx = new TickContext(1, 1, 123);

        $startTime = microtime(true);
        $kernel->runTick($state, $ctx);

        $this->assertTrue($criticalSlowEngine->executed, 'CRITICAL engine should always run');
        $this->assertFalse($cosmeticEngine->executed, 'COSMETIC engine should skip after threshold exceeded');
        $this->assertFalse($stochasticEngine->executed, 'STOCHASTIC engine should skip after threshold exceeded');
        $this->assertTrue($criticalFastEngine->executed, 'Late CRITICAL engine should still run even if threshold exceeded');
    }
}
