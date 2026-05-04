## Context

NarrativeLoomService goi HTTP POST voi timeout 600s. Khi Loom down, moi request block cho den 600s roi fail. Hien tai chi co try/catch + log. Social-Engine cung tuong tu voi 10s timeout. Frontend khong biet service nao up/down.

## Goals / Non-Goals

**Goals:**
- Implement circuit breaker pattern cho NarrativeLoomService
- Tao service-status endpoint check health cua Engine, Loom, Social-Engine, Redis, DB
- Frontend hook de consume service status

**Non-Goals:**
- Circuit breaker cho tat ca services (chi Loom truoc — longest timeout)
- Auto-recovery khi service khoi dong lai
- Alerting system

## Decisions

### D1: Circuit Breaker Implementation
**Decision:** Redis-backed circuit breaker voi 3 states: CLOSED (normal), OPEN (blocking), HALF_OPEN (testing). Threshold: 3 consecutive failures → OPEN for 60s. Dung Cache facade de portable.

### D2: Service Status Endpoint
**Decision:** `/api/worldos/service-status` tra ve JSON voi status per service. Check: DB (SELECT 1), Redis (ping), Engine (HTTP health), Loom (HTTP health), Social-Engine (HTTP health). Timeout 3s per check.

### D3: Frontend Hook
**Decision:** `useServiceStatus()` poll moi 30s. Hien thi summary (all green / partial / down).

## Risks / Trade-offs

- **[Risk] Circuit breaker false positive** → Mitigation: Threshold 3, not 1. Half-open probe after 60s
- **[Risk] Service health check adds latency** → Mitigation: Parallel checks, 3s timeout max
