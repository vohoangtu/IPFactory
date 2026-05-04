# engine-interface-standard Specification

## Purpose
TBD - created by archiving change refactor-simulation-loop. Update Purpose after archive.
## Requirements
### Requirement: All simulation engines extend AbstractWorldOSEngine
The system SHALL provide an `AbstractWorldOSEngine` base class that all simulation engines MUST extend.

#### Scenario: Engine declares its name
- **WHEN** an engine extends `AbstractWorldOSEngine`
- **THEN** it SHALL implement `name(): string` returning a unique identifier

#### Scenario: Engine declares its phase
- **WHEN** an engine extends `AbstractWorldOSEngine`
- **THEN** it SHALL implement `phase(): SimulationPhase` returning one of: Environment, Life, Mind, Social, Meta

#### Scenario: Engine implements execute method
- **WHEN** an engine extends `AbstractWorldOSEngine`
- **THEN** it SHALL implement `execute(WorldState $state, TickContext $ctx): EngineResult`

### Requirement: AbstractWorldOSEngine provides default lifecycle methods
The base class SHALL provide default implementations for common lifecycle behaviors.

#### Scenario: Default priority is zero
- **WHEN** an engine does not override `priority()`
- **THEN** the default priority SHALL be `0`

#### Scenario: Default enabled check returns true
- **WHEN** an engine does not override `isEnabled(WorldConfig $config)`
- **THEN** it SHALL return `true` (engine is enabled by default)

#### Scenario: Engine can be conditionally disabled
- **WHEN** an engine overrides `isEnabled()` to return `false` based on config
- **THEN** `PhaseRegistry` SHALL skip that engine during phase execution

### Requirement: EngineResult encapsulates execution output
Each engine execution SHALL return an `EngineResult` value object containing the outcome.

#### Scenario: EngineResult contains state mutations
- **WHEN** an engine completes execution
- **THEN** the `EngineResult` SHALL contain a list of state mutations to be applied

#### Scenario: EngineResult contains metrics
- **WHEN** an engine completes execution
- **THEN** the `EngineResult` SHALL contain execution metrics (duration_ms, entities_affected count)

#### Scenario: EngineResult supports skip status
- **WHEN** an engine determines it has no work to do
- **THEN** it SHALL return an `EngineResult` with `skipped: true` and a reason string

### Requirement: SimulationPhase is a backed enum
The system SHALL define `SimulationPhase` as a PHP 8.3 backed enum with the 5 canonical phases.

#### Scenario: Enum contains exactly 5 phases
- **WHEN** `SimulationPhase` is used
- **THEN** it SHALL have cases: `Environment`, `Life`, `Mind`, `Social`, `Meta`

#### Scenario: Phases have integer backing values
- **WHEN** phases are compared for ordering
- **THEN** they SHALL have integer values 1-5 matching execution order (Environment=1, Life=2, Mind=3, Social=4, Meta=5)

