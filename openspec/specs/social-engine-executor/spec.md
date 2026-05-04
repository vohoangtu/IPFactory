# social-engine-executor Specification

## Purpose
TBD - created by archiving change fix-critical-integrations. Update Purpose after archive.
## Requirements
### Requirement: Social-Engine swarm spawn executes simulation
The system SHALL execute actual swarm simulation when `/api/v1/swarm/spawn` is called.

#### Scenario: Spawn swarm with valid context
- **WHEN** POST `/api/v1/swarm/spawn` with valid WorldContext
- **THEN** the system SHALL start a simulation via SimulationRunner and return task_id

#### Scenario: Spawn swarm generates agent profiles
- **WHEN** a swarm simulation starts
- **THEN** the system SHALL generate agent profiles from WorldContext fields (era, social_structure, etc.)

#### Scenario: Spawn swarm with missing LLM key
- **WHEN** POST `/api/v1/swarm/spawn` and LLM_API_KEY is not configured
- **THEN** the system SHALL return error indicating missing configuration

