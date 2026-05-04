## ADDED Requirements

### Requirement: Service status endpoint reports health of all internal services
The system SHALL provide a `/api/worldos/service-status` endpoint reporting health of DB, Redis, Engine, NarrativeLoom, and Social-Engine.

#### Scenario: All services healthy
- **WHEN** GET `/api/worldos/service-status` and all services respond
- **THEN** the response SHALL contain status "ok" for each service

#### Scenario: Service unavailable
- **WHEN** GET `/api/worldos/service-status` and NarrativeLoom is down
- **THEN** the response SHALL contain status "error" for NarrativeLoom and "ok" for other healthy services
