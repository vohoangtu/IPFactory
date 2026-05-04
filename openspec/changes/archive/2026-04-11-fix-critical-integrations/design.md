## Context

Social-Engine co SimulationRunner (1764 lines, production-grade) nhung swarm_routes.py `/swarm/spawn` endpoint goi `run_swarm_simulation_task()` la `pass` — return 200 nhung khong lam gi. CentrifugoBroadcaster.auth() return true cho moi request — khong check channel access. Kafka event stream co full producer/consumer nhung disabled by default.

## Goals / Non-Goals

**Goals:**
- Implement `run_swarm_simulation_task()` de goi SimulationRunner.start_simulation()
- Implement Centrifugo channel authorization — check user co quyen subscribe universe channel
- Enable Kafka event streaming by default trong Docker environment

**Non-Goals:**
- Rewrite SimulationRunner (da production-ready)
- Implement full OASIS profile generator (separate change)
- Add Kafka consumer auto-start (manual CLI)
- Complex RBAC system cho channels

## Decisions

### D1: Social-Engine Task Executor
**Decision:** Goi SimulationRunner.start_simulation() voi WorldContext mapping. Generate simple agent profiles tu WorldContext thay vi full Zep integration (that can come later).

### D2: Channel Authorization
**Decision:** Parse channel name, extract universe_id, verify user co access (owner hoac public universe). `public:*` channels cho phep tat ca authenticated users. `universes:{id}` check Universe model.

### D3: Kafka Default Enable
**Decision:** Change default tu `false` sang `true` trong docker-compose env. Config PHP giu `false` de localhost dev khong bi loi.

## Risks / Trade-offs

- **[Risk] SimulationRunner can LLM API key** → Mitigation: Check config validation, return error neu thieu key
- **[Risk] Channel auth them latency** → Mitigation: Cache universe access cho 60s
- **[Risk] Kafka enable co the fail neu Redpanda chua ready** → Mitigation: Producer da co warning log, non-blocking
