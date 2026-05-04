## ADDED Requirements

### Requirement: Centrifugo broadcasting is enabled
The system SHALL set BROADCAST_DRIVER to 'centrifugo' so that simulation events are published to Centrifugo WebSocket server.

#### Scenario: Broadcasting enabled
- **WHEN** BROADCAST_DRIVER is set to 'centrifugo'
- **THEN** UniverseSimulationPulsed events SHALL be published to Centrifugo channels on each tick

### Requirement: Token endpoint provides JWT for WebSocket auth
The system SHALL provide a POST /api/centrifugo/token endpoint that returns a JWT signed with the Centrifugo HMAC secret.

#### Scenario: Token request
- **WHEN** frontend requests POST /api/centrifugo/token
- **THEN** the system SHALL return a JSON response with a valid JWT token
