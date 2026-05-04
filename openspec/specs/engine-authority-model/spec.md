# engine-authority-model Specification

## Purpose
TBD - created by archiving change rust-authoritative-boundary. Update Purpose after archive.
## Requirements
### Requirement: Engine Authority Classification
The system SHALL classify every PHP engine registered in PhaseRegistry into exactly one authority category: SUPPLEMENT, OVERLAP, STUB, or BRIDGE.

#### Scenario: Engine classified as SUPPLEMENT
- **WHEN** a PHP engine provides logic that Rust does not compute (e.g., NarrativeInterpretationEngine, MythogenesisEngine)
- **THEN** the engine SHALL be classified as SUPPLEMENT and SHALL always execute regardless of `rust_authoritative` config

#### Scenario: Engine classified as OVERLAP
- **WHEN** a PHP engine computes the same state fields that Rust already computes (e.g., ClimateEngine, MarketEngine)
- **THEN** the engine SHALL be classified as OVERLAP and SHALL be skipped when `rust_authoritative` is true

#### Scenario: Engine classified as STUB
- **WHEN** a PHP engine's handle() method returns only EngineResult::empty() with no computation
- **THEN** the engine SHALL be classified as STUB and SHALL be removed from PhaseRegistry registration

#### Scenario: Engine classified as BRIDGE
- **WHEN** a PHP engine exists solely as a wrapper that delegates to Rust (e.g., PotentialFieldEngine)
- **THEN** the engine SHALL be classified as BRIDGE and SHALL be skipped when `rust_authoritative` is true

### Requirement: EngineAuthority Enum
The system SHALL provide an `EngineAuthority` enum with cases: SUPPLEMENT, OVERLAP, BRIDGE.

#### Scenario: Enum used in LegacyEngineAdapter
- **WHEN** a LegacyEngineAdapter wraps a legacy engine for PhaseRegistry
- **THEN** it SHALL accept an EngineAuthority parameter alongside SimulationPhase

### Requirement: Runtime Authority Gating
PhaseRegistry SHALL skip engines with authority OVERLAP or BRIDGE when config `worldos_simulation.simulation.rust_authoritative` is true.

#### Scenario: Rust authoritative enabled
- **WHEN** `rust_authoritative` is true AND an engine has authority OVERLAP
- **THEN** PhaseRegistry SHALL NOT execute that engine and SHALL log a debug message

#### Scenario: Rust authoritative disabled (backward compat)
- **WHEN** `rust_authoritative` is false
- **THEN** PhaseRegistry SHALL execute ALL engines regardless of authority classification

### Requirement: PostSnapshotHandler Authority Gating
ALL PostSnapshotHandlers SHALL check `rust_authoritative` before executing, not just 3 of 9.

#### Scenario: Handler skips when Rust authoritative and key present
- **WHEN** `rust_authoritative` is true AND the handler's target state key already exists in state_vector
- **THEN** the handler SHALL skip its computation and return early

### Requirement: No Double Execution
Engines that are already called via RuleStage SHALL NOT also be registered in PhaseRegistry.

#### Scenario: RuleStage engine not in PhaseRegistry
- **WHEN** an engine (e.g., PostApotheosisEngine) is injected and called by RuleStage.run()
- **THEN** it SHALL NOT be registered in PhaseRegistry via KernelServiceProvider

