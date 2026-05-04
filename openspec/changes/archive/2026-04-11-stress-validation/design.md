## Context

SimulationSupervisor chay ticks sequential (1 tick/iteration). EngineDriver prepare state tu Redis/DB, goi engines, return response. TickManifest luu seed + engine results moi tick. SimulationReplayService load manifest + replay ticks, compare SHA256 state hash. TickMetricsService aggregate last 50 ticks. Tat ca infrastructure da co, chi thieu orchestration layer cho large-scale validation.

## Goals / Non-Goals

**Goals:**
- Tao `worldos:stress-test` command chay 100-5000+ ticks voi progress reporting
- Track memory usage, tick duration, va health score moi N ticks
- Tao `worldos:health-check` command verify tat ca services (DB, Redis, Neo4j)
- Tao feature test verify deterministic replay (20-50 ticks scale)
- Output summary report (avg ms, peak memory, total events, skip rate)

**Non-Goals:**
- Parallel/batch tick execution (giu sequential de don gian)
- Checkpoint/resume (se la change rieng)
- Load testing concurrent users (chi simulation engine stress)
- Modifying SimulationSupervisor or EngineDriver

## Decisions

### D1: Stress Test Command Architecture
**Decision:** Wrap SimulationSupervisor::execute() trong loop voi progress output. Khong modify Supervisor — chi observe va report.

### D2: Memory Monitoring
**Decision:** Dung `memory_get_peak_usage(true)` moi checkpoint interval (default 100 ticks). Output warning khi > 256MB.

### D3: Health Check Scope
**Decision:** Check 3 services: PostgreSQL (DB::select('SELECT 1')), Redis (Cache::get), Neo4j (HTTP GET port 7474). Return status per service.

### D4: Deterministic Replay Test Scale
**Decision:** 20 ticks (khong can Docker/gRPC — dung PHP engines only). Assert TickManifest hash match + event count match.

## Risks / Trade-offs

- **[Risk] 5000 ticks co the mat 10+ phut** → Mitigation: Progress bar + ETA. Default 100 ticks, 5000 la optional.
- **[Risk] Test can Docker cho full engine stack** → Mitigation: Feature test dung `config('worldos.simulation.engine_driver', 'php')` de bypass gRPC.
- **[Risk] Neo4j health check fail khi service off** → Mitigation: Non-fatal — report status, khong block other checks.
