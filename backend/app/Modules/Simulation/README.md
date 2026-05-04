# Simulation Module

## Overview
Module nay chiu trach nhiem dieu phoi su tien hoa cua vu tru (WorldOS Simulation) thong qua lang kinh cua Su gia (Narrative-Driven).

## Architecture (v6 Refactored)

### Kernel Model
- **WorldKernel**: Core orchestrator chay 5 phases (Environment, Life, Mind, Social, Meta) moi tick.
- **PhaseRegistry**: Quan ly dang ky cac engines theo phase. Engines duoc sap xep theo `priority()`, duplicate detection, va `isEnabled()` filtering.
- **AbstractWorldOSEngine**: Base class cho tat ca engines. Implement `WorldOSEngineInterface` (`name()`, `phase()`, `execute()`, `priority()`, `isEnabled()`).

### State Management
- **StateLoader**: Load WorldState tu database (actors, institutions, resources, ideas, chronicles, omens, supreme entities).
- **StateWriter**: Persist state ve database trong DB::transaction(). Batch-delete dead actors.
- **StateManager**: Facade class delegate toi StateLoader va StateWriter (deprecated — use truc tiep).
- **StateCacheManager**: Cache va holographic compression cua state vectors.

### Legacy (Deprecated)
- **EngineSystemAdapter**: Bridge legacy engines (SimulationEngine interface) vao WorldKernel orchestration map.
- **LegacyEngineAdapter**: Bridge legacy SimulationEngine implementations vao PhaseRegistry. Supports `EngineAuthority` classification.

### Engine Authority Model (Rust-Authoritative Boundary)

Khi Rust engine la source of truth (`rust_authoritative=true`), PhaseRegistry se tu dong filter cac legacy PHP engines theo authority classification:

| Authority | Behavior khi `rust_authoritative=true` | Behavior khi `false` |
|-----------|----------------------------------------|----------------------|
| **SUPPLEMENT** | Luon chay — Rust khong compute cac fields nay | Luon chay |
| **OVERLAP** | **Skip** — Rust da compute, PHP se ghi de sai | Chay binh thuong |
| **BRIDGE** | **Skip** — PHP wrapper goi Rust, du thua | Chay binh thuong |

**Config:** `worldos_simulation.simulation.rust_authoritative` (default: `true`)

**Engines classified as OVERLAP (10):** ClimateEngine, GeologicalEngine, CosmicPressureEngine, MetabolicEngine, GlobalEconomyEngine, MarketEngine, TradeEngine, WarEngine, PsychologyEngine, InequalityEngine

**Engines classified as BRIDGE (1):** ThermodynamicPhaseEngine

**PostSnapshotHandlers:** Cung duoc gate boi `rust_authoritative` — skip khi Rust da cung cap data tuong ung trong state_vector.

## Key Enums & Value Objects
- `SimulationPhase` — Backed enum (Environment=1, Life=2, Mind=3, Social=4, Meta=5)
- `EngineResult` — Engine execution output (stateChanges, events, metrics, skipped, skipReason)
- `TickContext` — Tick metadata (universeId, tick, seed, metadata)
- `PhaseExecutionResult` — Collects EngineResults per phase

## Structure
- `Actions/`: Use case handlers
- `Core/Runtime/`: WorldKernel, PhaseExecutor, StateManager, WorldState
- `Core/Engines/`: Legacy engine implementations (Physics/, Social/, Meta/, Environment/)
- `Core/Domain/`: EngineResult, TickContext, PhaseExecutionResult, SimulationTickResult
- `Core/Contracts/`: SimulationEngine interface, Effect, StateCacheInterface
- `Engines/`: AbstractWorldOSEngine, LegacyEngineAdapter (v2 engine model)
- `Enums/`: SimulationPhase, EngineAuthority
- `Exceptions/`: StateWriteException
- `Services/Kernel/`: PhaseRegistry
- `Providers/`: KernelServiceProvider, PipelineServiceProvider, EngineServiceProvider

## Engine Registration

### v2 (Preferred — PhaseRegistry)
```php
// In KernelServiceProvider:
$registry = $app->make(PhaseRegistry::class);
$registry->register(new MyNewEngine());

// Legacy engines with authority classification:
$registry->register(new LegacyEngineAdapter(
    $engine,
    SimulationPhase::Social,
    EngineAuthority::OVERLAP  // Skip when Rust is authoritative
));
```

### Legacy (orchestrationMap)
```php
$kernel->registerSystem(
    WorldKernel::PHASE_ENVIRONMENT,
    WorldKernel::RULE_METABOLISM,
    new EngineSystemAdapter($engine)
);
```

## Creating a New Engine
```php
class MyEngine extends AbstractWorldOSEngine
{
    public function name(): string { return 'my-engine'; }
    public function phase(): SimulationPhase { return SimulationPhase::Environment; }
    public function priority(): int { return 10; }

    public function execute(WorldState $state, TickContext $ctx): EngineResult
    {
        // Your computation logic
        return EngineResult::empty();
    }
}
```

## Testing
```bash
php artisan test --filter=Simulation
```
