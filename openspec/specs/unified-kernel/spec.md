# unified-kernel Specification

## Purpose
TBD - created by archiving change refactor-simulation-loop. Update Purpose after archive.
## Requirements
### Requirement: WorldKernel is the sole simulation orchestrator
The system SHALL use `WorldKernel` as the single canonical orchestrator for all simulation ticks. No alternative kernel class SHALL be used for tick execution in production.

#### Scenario: Simulation tick uses WorldKernel
- **WHEN** a simulation tick is triggered via `AdvanceSimulationAction`
- **THEN** the tick SHALL be executed through `WorldKernel::execute()` and NOT through `SimulationKernel`

#### Scenario: SimulationKernel is deprecated
- **WHEN** any code references `SimulationKernel`
- **THEN** the class SHALL be marked `@deprecated` with a message directing to `WorldKernel`

### Requirement: WorldKernel uses PhaseRegistry for engine orchestration
The system SHALL use a `PhaseRegistry` to manage engine registration and execution order. PhaseRegistry SHALL support authority-aware filtering — skipping OVERLAP and BRIDGE engines when `rust_authoritative` config is true.

#### Scenario: WorldKernel constructor accepts PhaseRegistry
- **WHEN** `WorldKernel` is instantiated
- **THEN** it SHALL accept a `PhaseRegistry` instance, an `EventDispatcher`, and a state management interface — no more than 10 constructor parameters total

#### Scenario: Engines are registered per phase
- **WHEN** engines are registered in the `PhaseRegistry`
- **THEN** each engine SHALL be associated with exactly one of the 5 phases: Environment, Life, Mind, Social, Meta

#### Scenario: Phase execution order is preserved
- **WHEN** `WorldKernel::execute()` runs a tick
- **THEN** phases SHALL execute in strict order: Environment → Life → Mind → Social → Meta

#### Scenario: OVERLAP engines skipped when Rust authoritative
- **WHEN** `rust_authoritative` is true AND `PhaseRegistry::getEnginesForPhase()` is called
- **THEN** engines with authority OVERLAP or BRIDGE SHALL be excluded from the returned list

#### Scenario: All engines execute when Rust not authoritative
- **WHEN** `rust_authoritative` is false AND `PhaseRegistry::getEnginesForPhase()` is called
- **THEN** ALL registered engines SHALL be returned regardless of authority classification

### Requirement: PhaseRegistry supports priority-based engine ordering
Within each phase, engines SHALL execute in order determined by their `priority()` value (lower values execute first).

#### Scenario: Engines execute by priority within phase
- **WHEN** a phase contains engines with priorities 10, 5, and 20
- **THEN** engines SHALL execute in order: priority 5 → priority 10 → priority 20

#### Scenario: Equal priority engines have deterministic order
- **WHEN** two engines in the same phase have equal priority
- **THEN** they SHALL execute in a deterministic order (alphabetical by engine name)

### Requirement: Configuration toggle is removed
The `simulation_tick_driver` config flag SHALL be removed after migration. During migration, it SHALL default to `world_kernel`.

#### Scenario: Config flag defaults to world_kernel during migration
- **WHEN** the system reads `simulation_tick_driver` config during migration period
- **THEN** the default value SHALL be `world_kernel`

#### Scenario: Config flag is removed post-migration
- **WHEN** migration is complete (Phase 3)
- **THEN** the `simulation_tick_driver` config key SHALL be removed from `config/worldos.php`

