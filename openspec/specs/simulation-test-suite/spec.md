# simulation-test-suite Specification

## Purpose
TBD - created by archiving change refactor-simulation-loop. Update Purpose after archive.
## Requirements
### Requirement: WorldKernel has unit test coverage
The system SHALL have unit tests covering `WorldKernel` phase orchestration logic.

#### Scenario: Test phases execute in correct order
- **WHEN** the WorldKernel unit test suite runs
- **THEN** there SHALL be a test verifying phases execute in order: Environment → Life → Mind → Social → Meta

#### Scenario: Test engine skip when disabled
- **WHEN** the WorldKernel unit test suite runs
- **THEN** there SHALL be a test verifying that disabled engines (isEnabled returns false) are skipped

#### Scenario: Test priority ordering within phase
- **WHEN** the WorldKernel unit test suite runs
- **THEN** there SHALL be a test verifying engines within a phase execute by priority order

### Requirement: StateLoader has unit test coverage
The system SHALL have unit tests covering `StateLoader` state reconstruction.

#### Scenario: Test full state loading
- **WHEN** the StateLoader unit test suite runs
- **THEN** there SHALL be a test verifying all entity types are loaded into WorldState (actors, institutions, resources, ideas, omens, chronicles, supreme entities)

#### Scenario: Test cache hit path
- **WHEN** the StateLoader unit test suite runs
- **THEN** there SHALL be a test verifying that cached state is returned without database query

### Requirement: StateWriter has unit test coverage
The system SHALL have unit tests covering `StateWriter` persistence logic.

#### Scenario: Test batch save actors
- **WHEN** the StateWriter unit test suite runs
- **THEN** there SHALL be a test verifying actors are saved in batch (not individual inserts)

#### Scenario: Test batch delete dead actors
- **WHEN** the StateWriter unit test suite runs
- **THEN** there SHALL be a test verifying dead actors are deleted with a single batch query

#### Scenario: Test transaction rollback on failure
- **WHEN** the StateWriter unit test suite runs
- **THEN** there SHALL be a test verifying the transaction rolls back when a write fails

### Requirement: PhaseRegistry has unit test coverage
The system SHALL have unit tests covering `PhaseRegistry` engine registration and retrieval.

#### Scenario: Test engine registration and retrieval by phase
- **WHEN** the PhaseRegistry unit test suite runs
- **THEN** there SHALL be a test verifying engines can be registered and retrieved by phase

#### Scenario: Test duplicate engine detection
- **WHEN** the PhaseRegistry unit test suite runs
- **THEN** there SHALL be a test verifying that registering an engine with a duplicate name throws an exception

### Requirement: gRPC integration has feature test coverage
The system SHALL have feature tests verifying the integration between Laravel pipeline and Rust gRPC engine.

#### Scenario: Test EngineDriver advance call
- **WHEN** the gRPC feature test suite runs
- **THEN** there SHALL be a test verifying `EngineDriver::advance()` correctly sends state and receives results (using a mock gRPC server)

#### Scenario: Test gRPC timeout handling
- **WHEN** the gRPC feature test suite runs
- **THEN** there SHALL be a test verifying proper error handling when gRPC call times out

### Requirement: Social engine smoke tests exist
Each implemented Social engine (GlobalEconomyEngine, MarketEngine, TradeEngine, InequalityEngine, PoliticsEngine, CultureEngine) SHALL have at least one smoke test that calls handle() with valid WorldState and verifies no exception is thrown and an EngineResult is returned.

#### Scenario: Social engine handles valid input
- **WHEN** a Social engine's handle() is called with a WorldState containing zones and a valid TickContext
- **THEN** the engine SHALL return an EngineResult without throwing

### Requirement: Environment engine smoke tests exist
ClimateEngine and GeologicalEngine SHALL have smoke tests verifying handle() returns EngineResult with valid zone state.

#### Scenario: Environment engine handles valid input
- **WHEN** an Environment engine's handle() is called with valid WorldState
- **THEN** the engine SHALL return an EngineResult without throwing

### Requirement: Meta engine representative tests exist
At least 5 representative Meta engines SHALL have smoke tests verifying handle() executes without error.

#### Scenario: Meta engine handles valid input
- **WHEN** a Meta engine's handle() is called with valid WorldState
- **THEN** the engine SHALL return an EngineResult without throwing

### Requirement: WorldKernel integration test covers full tick
A test SHALL execute a full tick cycle through WorldKernel with PhaseRegistry containing registered engines and verify all phases complete.

#### Scenario: Full tick cycle completes
- **WHEN** WorldKernel runs a tick with engines registered in PhaseRegistry
- **THEN** all 5 phases SHALL execute and return results without error

