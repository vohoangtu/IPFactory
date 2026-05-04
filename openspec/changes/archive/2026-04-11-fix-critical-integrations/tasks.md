## 1. Social-Engine Executor

- [x] 1.1 Implement `run_swarm_simulation_task()` in `swarm_routes.py` — call SimulationRunner with WorldContext mapping
- [x] 1.2 Create `swarm_profile_factory.py` in `app/services/` — generate agent profiles from WorldContext without Zep dependency

## 2. Centrifugo Channel Authorization

- [x] 2.1 Implement `auth()` in CentrifugoBroadcaster — parse channel, check `public:*` vs `universes:{id}`, verify universe exists

## 3. Kafka Event Streaming

- [x] 3.1 Enable Kafka by default in `docker-compose.prod.yml` — set `WORLDOS_EVENT_STREAM_KAFKA_ENABLED=true`

## 4. Cleanup

- [x] 4.1 Update `.dev_status.md`
