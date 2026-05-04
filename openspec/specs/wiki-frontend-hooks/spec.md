# wiki-frontend-hooks Specification

## Purpose
TBD - created by archiving change improve-service-integration. Update Purpose after archive.
## Requirements
### Requirement: Frontend hooks for Knowledge/Wiki module
The frontend SHALL provide hooks to access wiki search, actor wiki, and axioms.

#### Scenario: Wiki search
- **WHEN** `useWikiSearch(universeId, query)` is called with a query string
- **THEN** the system SHALL fetch from `/wiki/{universeId}/search?q={query}`

#### Scenario: Actor wiki
- **WHEN** `useActorWiki(universeId, actorId)` is called
- **THEN** the system SHALL fetch from `/wiki/{universeId}/actor/{actorId}`

#### Scenario: Axioms list
- **WHEN** `useAxioms()` is called
- **THEN** the system SHALL fetch from `/wiki/axioms`

