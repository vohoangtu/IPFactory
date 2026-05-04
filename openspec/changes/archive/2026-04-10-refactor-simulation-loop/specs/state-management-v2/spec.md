## ADDED Requirements

### Requirement: StateManager is decomposed into single-responsibility classes
The system SHALL split the monolithic `StateManager` into three specialized classes: `StateLoader`, `StateWriter`, and `StateCacheManager`.

#### Scenario: StateLoader handles all read operations
- **WHEN** simulation state needs to be loaded for a tick
- **THEN** `StateLoader` SHALL reconstruct the full `WorldState` by loading actors, institutions, resources, ideas, omens, chronicles, and supreme entities from database or cache

#### Scenario: StateWriter handles all write operations
- **WHEN** simulation state needs to be persisted after a tick
- **THEN** `StateWriter` SHALL batch-save all modified entities (actors, institutions, universe) in optimized queries

#### Scenario: StateCacheManager handles caching
- **WHEN** state caching is enabled (`state_cache.driver` is not `null`)
- **THEN** `StateCacheManager` SHALL manage cache read/write/invalidation for state vectors and holographic state

### Requirement: Dead actors are deleted in batch
The system SHALL delete dead actors using a single batch query instead of individual deletes.

#### Scenario: Batch delete dead actors
- **WHEN** a tick produces dead actors to be removed
- **THEN** `StateWriter` SHALL execute a single `DELETE WHERE id IN (...)` query, NOT individual delete calls per actor

#### Scenario: Batch delete handles empty list
- **WHEN** no actors died during the tick
- **THEN** `StateWriter` SHALL skip the delete query entirely (no empty WHERE IN)

### Requirement: StateManager facade maintains backward compatibility
A `StateManager` facade class SHALL exist during migration that delegates to the three new classes.

#### Scenario: Existing code using StateManager::load() still works
- **WHEN** legacy code calls `StateManager::load()`
- **THEN** the call SHALL be delegated to `StateLoader::load()` and return the same `WorldState` result

#### Scenario: Existing code using StateManager::save() still works
- **WHEN** legacy code calls `StateManager::save()`
- **THEN** the call SHALL be delegated to `StateWriter::save()` with identical behavior

#### Scenario: StateManager facade methods are marked deprecated
- **WHEN** `StateManager::load()` or `StateManager::save()` is called
- **THEN** the methods SHALL trigger a `@deprecated` notice directing to the new classes

### Requirement: StateWriter supports transactional saves
All state write operations within a single tick SHALL be wrapped in a database transaction.

#### Scenario: All writes in a tick are atomic
- **WHEN** `StateWriter::save()` is called after a tick
- **THEN** actor saves, institution saves, universe save, and dead actor deletes SHALL all execute within a single database transaction

#### Scenario: Transaction rolls back on failure
- **WHEN** any write operation fails during save
- **THEN** the entire transaction SHALL be rolled back and a `StateWriteException` SHALL be thrown
