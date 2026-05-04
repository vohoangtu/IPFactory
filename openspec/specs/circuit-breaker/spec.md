# circuit-breaker Specification

## Purpose
TBD - created by archiving change service-hardening. Update Purpose after archive.
## Requirements
### Requirement: Circuit breaker prevents cascading failures
The system SHALL implement a circuit breaker for NarrativeLoomService that opens after consecutive failures.

#### Scenario: Circuit opens after 3 failures
- **WHEN** NarrativeLoomService fails 3 consecutive calls
- **THEN** the circuit SHALL open and subsequent calls SHALL fail fast without making HTTP requests for 60 seconds

#### Scenario: Circuit half-opens after cooldown
- **WHEN** 60 seconds have passed since the circuit opened
- **THEN** the next call SHALL be allowed as a probe (half-open state)

#### Scenario: Successful probe closes circuit
- **WHEN** a probe call succeeds in half-open state
- **THEN** the circuit SHALL close and resume normal operation

