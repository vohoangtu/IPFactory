# narrative-trigger-ui Specification

## Purpose
TBD - created by archiving change improve-service-integration. Update Purpose after archive.
## Requirements
### Requirement: Frontend can trigger narrative generation
The frontend SHALL provide hooks to trigger chronicle generation and receive completion notifications.

#### Scenario: Generate chronicle
- **WHEN** `useGenerateChronicle()` mutation is called with universe ID
- **THEN** the system SHALL POST to `/worldos/universes/{id}/generate-chronicle` and invalidate chronicles cache on success

#### Scenario: Narrative completion broadcast
- **WHEN** NarrativeLoomService completes a chronicle weave
- **THEN** the backend SHALL broadcast a message to `public:universes` Centrifugo channel

