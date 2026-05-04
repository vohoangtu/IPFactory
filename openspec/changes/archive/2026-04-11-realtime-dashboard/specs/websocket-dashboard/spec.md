## ADDED Requirements

### Requirement: WebSocket connection hook manages lifecycle
The frontend SHALL provide a useCentrifugoConnection hook that manages WebSocket connection lifecycle, exposes connection state, and handles auto-reconnect.

#### Scenario: Connection established
- **WHEN** the dashboard mounts and Centrifugo URL is configured
- **THEN** useCentrifugoConnection SHALL establish a WebSocket connection

#### Scenario: Connection lost and recovered
- **WHEN** the WebSocket connection drops
- **THEN** the hook SHALL auto-reconnect with exponential backoff

### Requirement: Dashboard uses WebSocket for cache invalidation
The dashboard SHALL subscribe to universe channels and invalidate React Query cache on message receipt, replacing 10-15s polling with realtime updates.

#### Scenario: Simulation tick received via WebSocket
- **WHEN** a WebSocket message is received on universes:{id} channel
- **THEN** React Query cache for metrics, snapshots, and dossier SHALL be invalidated

### Requirement: Graceful fallback to polling
The dashboard SHALL fallback to 60s polling when WebSocket is disconnected, and disable polling when WebSocket is connected.

#### Scenario: WebSocket disconnected
- **WHEN** WebSocket connection is lost
- **THEN** polling SHALL resume at 60s interval

#### Scenario: WebSocket reconnected
- **WHEN** WebSocket connection is restored
- **THEN** polling SHALL be disabled
