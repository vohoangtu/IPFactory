## ADDED Requirements

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
