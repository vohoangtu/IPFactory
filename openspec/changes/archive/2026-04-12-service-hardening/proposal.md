## Why

NarrativeLoomService timeout 600s ma khong co circuit breaker — khi Loom down, moi request block 600s truoc khi fail. Backend khong co middleware verify service-to-service calls tu internal services. Frontend khong co cach nao biet trang thai cac services (engine, loom, social-engine).

## What Changes

- **Circuit breaker**: NarrativeLoomService dung circuit breaker pattern — sau N failures lien tiep, skip calls cho M giay
- **Service status endpoint**: Backend endpoint `/api/worldos/service-status` tra ve health cua tat ca internal services
- **Frontend service status hook**: Hook de hien thi service status tren dashboard

## Capabilities

### New Capabilities
- `circuit-breaker`: Circuit breaker pattern cho NarrativeLoomService external calls
- `service-status-endpoint`: Backend API endpoint exposing internal service health

### Modified Capabilities

## Impact

- `backend/app/Modules/Narrative/Services/NarrativeLoomService.php` — add circuit breaker
- `backend/app/Services/CircuitBreaker.php` — new circuit breaker utility
- `backend/app/Modules/WorldOS/Http/Controllers/Api/ServiceStatusController.php` — new controller
- `backend/app/Modules/WorldOS/routes/api.php` — add service-status route
- `frontend/src/hooks/useServiceStatus.ts` — new hook
