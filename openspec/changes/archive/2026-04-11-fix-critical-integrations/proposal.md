## Why

3 critical integration gaps: Social-Engine task executor is completely stubbed (returns 200 but does nothing), Centrifugo channel authorization is bypassed (any user subscribes to any channel), and Kafka event streaming is disabled by default despite infrastructure being ready. These need fixing before any production validation.

## What Changes

- **Social-Engine**: Implement `run_swarm_simulation_task()` with actual swarm logic — agent generation, trait assignment, interaction loop, result persistence
- **Centrifugo**: Implement channel-level authorization checking universe ownership/access before allowing subscription
- **Kafka**: Enable event streaming by default in Docker, ensure producer publishes after each tick, verify consumer CLI commands work

## Capabilities

### New Capabilities
- `channel-authorization`: Centrifugo channel authorization verifying user access to universe-specific channels
- `social-engine-executor`: Social-Engine swarm task executor with agent generation and interaction loop

### Modified Capabilities
- `realtime-broadcast`: Adding channel authorization to existing Centrifugo broadcasting

## Impact

- `sim/social-engine/app/api/swarm_routes.py` — implement task executor
- `sim/social-engine/app/services/` — new swarm simulation service
- `backend/app/Broadcasting/CentrifugoBroadcaster.php` — channel auth logic
- `backend/config/worldos_simulation.php` — Kafka enabled by default
- `deployment/docker-compose.prod.yml` — Kafka env vars
