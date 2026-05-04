## MODIFIED Requirements

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
