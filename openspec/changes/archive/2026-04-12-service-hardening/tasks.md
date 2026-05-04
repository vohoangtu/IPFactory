## 1. Circuit Breaker

- [x] 1.1 Create `backend/app/Services/CircuitBreaker.php` — Redis-backed circuit breaker with CLOSED/OPEN/HALF_OPEN states
- [x] 1.2 Integrate circuit breaker into NarrativeLoomService `weave()` and `getActorIntent()` methods

## 2. Service Status

- [x] 2.1 Create `ServiceStatusController.php` — check health of DB, Redis, Engine, Loom, Social-Engine
- [x] 2.2 Add route `GET /api/worldos/service-status` in WorldOS routes
- [x] 2.3 Create `frontend/src/hooks/useServiceStatus.ts` — hook to poll service status

## 3. Cleanup

- [x] 3.1 Update `.dev_status.md`
