## ADDED Requirements

### Requirement: Centrifugo channel authorization verifies user access
The system SHALL verify user access before allowing Centrifugo channel subscription.

#### Scenario: Public channel subscription
- **WHEN** a user subscribes to `public:universes`
- **THEN** the system SHALL allow the subscription for any authenticated user

#### Scenario: Universe-specific channel with access
- **WHEN** a user subscribes to `universes:{id}` and the universe is active
- **THEN** the system SHALL allow the subscription

#### Scenario: Universe-specific channel without access
- **WHEN** a user subscribes to `universes:{id}` and the universe does not exist
- **THEN** the system SHALL deny the subscription
