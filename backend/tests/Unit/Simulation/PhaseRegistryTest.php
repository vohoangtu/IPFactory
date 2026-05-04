<?php

declare(strict_types=1);

namespace Tests\Unit\Simulation;

use App\Modules\Simulation\Core\Domain\EngineResult;
use App\Modules\Simulation\Core\Domain\TickContext;
use App\Modules\Simulation\Core\Runtime\State\WorldState;
use App\Modules\Simulation\Engines\AbstractWorldOSEngine;
use App\Modules\Simulation\Engines\LegacyEngineAdapter;
use App\Modules\Simulation\Enums\EngineAuthority;
use App\Modules\Simulation\Enums\SimulationPhase;
use App\Modules\Simulation\Services\Kernel\PhaseRegistry;
use Tests\TestCase;

class PhaseRegistryTest extends TestCase
{
    private PhaseRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new PhaseRegistry();
    }

    // --------------------------------------------------
    // Registration & Retrieval
    // --------------------------------------------------

    public function test_register_and_retrieve_engine_by_phase(): void
    {
        $engine = $this->makeEngine('test-engine', SimulationPhase::Environment);

        $this->registry->register($engine);

        $engines = $this->registry->getEnginesForPhase(SimulationPhase::Environment);

        $this->assertCount(1, $engines);
        $this->assertSame('test-engine', $engines[0]->name());
    }

    public function test_retrieve_returns_empty_for_phase_with_no_engines(): void
    {
        $this->assertEmpty($this->registry->getEnginesForPhase(SimulationPhase::Meta));
    }

    public function test_get_all_phases_returns_only_phases_with_engines(): void
    {
        $this->registry->register($this->makeEngine('e1', SimulationPhase::Environment));
        $this->registry->register($this->makeEngine('e2', SimulationPhase::Meta));

        $phases = $this->registry->getAllPhases();

        $this->assertCount(2, $phases);
        $this->assertSame(SimulationPhase::Environment, $phases[0]);
        $this->assertSame(SimulationPhase::Meta, $phases[1]);
    }

    public function test_count_returns_total_registered_engines(): void
    {
        $this->registry->register($this->makeEngine('e1', SimulationPhase::Environment));
        $this->registry->register($this->makeEngine('e2', SimulationPhase::Life));
        $this->registry->register($this->makeEngine('e3', SimulationPhase::Environment));

        $this->assertSame(3, $this->registry->count());
    }

    // --------------------------------------------------
    // Priority Sorting
    // --------------------------------------------------

    public function test_engines_sorted_by_priority_ascending(): void
    {
        $this->registry->register($this->makeEngine('high', SimulationPhase::Environment, priority: 20));
        $this->registry->register($this->makeEngine('low', SimulationPhase::Environment, priority: 5));
        $this->registry->register($this->makeEngine('mid', SimulationPhase::Environment, priority: 10));

        $engines = $this->registry->getEnginesForPhase(SimulationPhase::Environment);

        $this->assertSame('low', $engines[0]->name());
        $this->assertSame('mid', $engines[1]->name());
        $this->assertSame('high', $engines[2]->name());
    }

    public function test_equal_priority_sorted_alphabetically_by_name(): void
    {
        $this->registry->register($this->makeEngine('zeta-engine', SimulationPhase::Life, priority: 0));
        $this->registry->register($this->makeEngine('alpha-engine', SimulationPhase::Life, priority: 0));
        $this->registry->register($this->makeEngine('beta-engine', SimulationPhase::Life, priority: 0));

        $engines = $this->registry->getEnginesForPhase(SimulationPhase::Life);

        $this->assertSame('alpha-engine', $engines[0]->name());
        $this->assertSame('beta-engine', $engines[1]->name());
        $this->assertSame('zeta-engine', $engines[2]->name());
    }

    // --------------------------------------------------
    // Duplicate Detection
    // --------------------------------------------------

    public function test_duplicate_engine_name_throws_exception(): void
    {
        $this->registry->register($this->makeEngine('dup', SimulationPhase::Environment));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Duplicate engine name: 'dup'");

        $this->registry->register($this->makeEngine('dup', SimulationPhase::Life));
    }

    // --------------------------------------------------
    // isEnabled Filtering
    // --------------------------------------------------

    public function test_disabled_engines_are_excluded(): void
    {
        $this->registry->register($this->makeEngine('enabled', SimulationPhase::Social, enabled: true));
        $this->registry->register($this->makeEngine('disabled', SimulationPhase::Social, enabled: false));

        $engines = $this->registry->getEnginesForPhase(SimulationPhase::Social);

        $this->assertCount(1, $engines);
        $this->assertSame('enabled', $engines[0]->name());
    }

    // --------------------------------------------------
    // Authority-Aware Filtering (rust-authoritative-boundary)
    // --------------------------------------------------

    public function test_overlap_engines_skipped_when_rust_authoritative(): void
    {
        $supplement = $this->makeLegacyAdapter('supplement-engine', SimulationPhase::Social, EngineAuthority::SUPPLEMENT);
        $overlap = $this->makeLegacyAdapter('overlap-engine', SimulationPhase::Social, EngineAuthority::OVERLAP);
        $bridge = $this->makeLegacyAdapter('bridge-engine', SimulationPhase::Social, EngineAuthority::BRIDGE);

        $this->registry->register($supplement);
        $this->registry->register($overlap);
        $this->registry->register($bridge);

        $engines = $this->registry->getEnginesForPhase(SimulationPhase::Social, [], rustAuthoritative: true);

        $this->assertCount(1, $engines);
        $this->assertSame('supplement-engine', $engines[0]->name());
    }

    public function test_all_engines_returned_when_rust_not_authoritative(): void
    {
        $supplement = $this->makeLegacyAdapter('supplement-engine', SimulationPhase::Social, EngineAuthority::SUPPLEMENT);
        $overlap = $this->makeLegacyAdapter('overlap-engine', SimulationPhase::Social, EngineAuthority::OVERLAP);
        $bridge = $this->makeLegacyAdapter('bridge-engine', SimulationPhase::Social, EngineAuthority::BRIDGE);

        $this->registry->register($supplement);
        $this->registry->register($overlap);
        $this->registry->register($bridge);

        $engines = $this->registry->getEnginesForPhase(SimulationPhase::Social, [], rustAuthoritative: false);

        $this->assertCount(3, $engines);
    }

    public function test_legacy_adapter_stores_and_returns_authority(): void
    {
        $adapter = $this->makeLegacyAdapter('test', SimulationPhase::Environment, EngineAuthority::OVERLAP);
        $this->assertSame(EngineAuthority::OVERLAP, $adapter->getAuthority());

        $defaultAdapter = $this->makeLegacyAdapter('test2', SimulationPhase::Environment);
        $this->assertSame(EngineAuthority::SUPPLEMENT, $defaultAdapter->getAuthority());
    }

    public function test_non_legacy_engines_unaffected_by_rust_authoritative(): void
    {
        // Plain AbstractWorldOSEngine (not LegacyEngineAdapter) should always run
        $plainEngine = $this->makeEngine('plain-engine', SimulationPhase::Social);
        $overlap = $this->makeLegacyAdapter('overlap-engine', SimulationPhase::Social, EngineAuthority::OVERLAP);

        $this->registry->register($plainEngine);
        $this->registry->register($overlap);

        $engines = $this->registry->getEnginesForPhase(SimulationPhase::Social, [], rustAuthoritative: true);

        $this->assertCount(1, $engines);
        $this->assertSame('plain-engine', $engines[0]->name());
    }

    // --------------------------------------------------
    // Helpers
    // --------------------------------------------------

    private function makeLegacyAdapter(
        string $name,
        SimulationPhase $phase,
        EngineAuthority $authority = EngineAuthority::SUPPLEMENT,
    ): LegacyEngineAdapter {
        $mockEngine = new class($name) implements \App\Modules\Simulation\Core\Contracts\SimulationEngine {
            public function __construct(private readonly string $engineName) {}
            public function name(): string { return $this->engineName; }
            public function version(): string { return '1.0.0'; }
            public function phase(): string { return 'META'; }
            public function priority(): int { return 0; }
            public function priorityCategory(): string { return 'NORMAL'; }
            public function tickRate(): int { return 1; }
            public function isParallelSafe(): bool { return false; }
            public function handle(\App\Modules\Simulation\Core\Runtime\State\WorldState $state, \App\Modules\Simulation\Core\Domain\TickContext $ctx): \App\Modules\Simulation\Core\Engines\EngineResult {
                return new \App\Modules\Simulation\Core\Engines\EngineResult([], []);
            }
        };
        return new LegacyEngineAdapter($mockEngine, $phase, $authority);
    }

    private function makeEngine(
        string $name,
        SimulationPhase $phase,
        int $priority = 0,
        bool $enabled = true,
    ): AbstractWorldOSEngine {
        return new class($name, $phase, $priority, $enabled) extends AbstractWorldOSEngine {
            public function __construct(
                private readonly string $engineName,
                private readonly SimulationPhase $enginePhase,
                private readonly int $enginePriority,
                private readonly bool $engineEnabled,
            ) {
            }

            public function name(): string { return $this->engineName; }
            public function phase(): SimulationPhase { return $this->enginePhase; }
            public function priority(): int { return $this->enginePriority; }
            public function isEnabled(array $config = []): bool { return $this->engineEnabled; }

            public function execute(WorldState $state, TickContext $ctx): EngineResult
            {
                return EngineResult::empty();
            }
        };
    }
}
