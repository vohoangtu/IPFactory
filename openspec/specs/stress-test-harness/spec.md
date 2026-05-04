# stress-test-harness Specification

## Purpose
TBD - created by archiving change stress-validation. Update Purpose after archive.
## Requirements
### Requirement: Stress test command runs N ticks with reporting
The system SHALL provide a `worldos:stress-test` artisan command that executes N simulation ticks on a universe with progress bar, memory tracking, and summary report.

#### Scenario: Run stress test with default settings
- **WHEN** `php artisan worldos:stress-test {universeId}` is executed
- **THEN** the command SHALL run 100 ticks with progress output and print a summary

#### Scenario: Run stress test with custom tick count
- **WHEN** `php artisan worldos:stress-test {universeId} --ticks=5000` is executed
- **THEN** the command SHALL run 5000 ticks and report progress every 100 ticks

#### Scenario: Memory warning threshold
- **WHEN** peak memory exceeds 256MB during stress test
- **THEN** the command SHALL output a warning

### Requirement: Health check command verifies service connectivity
The system SHALL provide a `worldos:health-check` artisan command that tests DB, Redis, and Neo4j connectivity and reports status per service.

#### Scenario: All services healthy
- **WHEN** `php artisan worldos:health-check` is executed and all services respond
- **THEN** the command SHALL output OK for each service and exit with code 0

#### Scenario: Neo4j unavailable
- **WHEN** Neo4j is not running
- **THEN** the command SHALL report Neo4j as FAIL and exit with code 1

### Requirement: Deterministic replay test validates seed reproducibility
A feature test SHALL run N ticks, then replay the same ticks using saved TickManifest seeds, and verify the state hash matches.

#### Scenario: Replay matches original
- **WHEN** 20 ticks are executed and then replayed with the same seeds
- **THEN** the final state_vector SHA256 hash SHALL match the original

