## Why

Dashboard hien polling 5 endpoints moi 10-15 giay, gay tai cho backend va delay data. Infrastructure Centrifugo da duoc setup (CentrifugoBroadcaster, UniverseSimulationPulsed event, Centrifuge client) nhung BROADCAST_DRIVER='null' nen khong hoat dong. Can kich hoat WebSocket va tao React hooks de nhan realtime updates thay vi polling.

## What Changes

- **Backend:** Enable Centrifugo broadcasting, them token endpoint cho WebSocket auth, broadcast events tren mutation routes
- **Frontend:** Tao WebSocket hooks (connection, subscription, cache invalidation), chuyen dashboard tu polling sang realtime, giu fallback polling khi WebSocket mat ket noi
- **Config:** Set BROADCAST_DRIVER=centrifugo

## Capabilities

### New Capabilities
- `realtime-broadcast`: Backend Centrifugo broadcasting activation va token endpoint
- `websocket-dashboard`: Frontend WebSocket hooks va dashboard migration tu polling sang realtime

### Modified Capabilities

## Impact

- `backend/.env.example` — add BROADCAST_DRIVER=centrifugo
- `backend/app/Modules/WorldOS/routes/api.php` — them token endpoint
- `backend/app/Modules/WorldOS/Http/Controllers/` — them CentrifugoController
- `frontend/src/hooks/useCentrifugo.ts` — WebSocket connection + subscription hooks
- `frontend/src/features/simulation/api/queries.ts` — chuyen tu polling sang realtime
- `frontend/src/features/universe/api/queries.ts` — chuyen tu polling sang realtime
