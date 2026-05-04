## Context

Centrifugo da duoc setup trong Docker stack (port 8000). CentrifugoBroadcaster da implement (JWT token, batch publish). UniverseSimulationPulsed event fires moi tick. Nhung BROADCAST_DRIVER='null' nen khong co broadcast nao thuc su xay ra. Frontend co centrifuge client nhung chi dung trong narrative-studio.

## Goals / Non-Goals

**Goals:**
- Kich hoat Centrifugo broadcasting bang cach set BROADCAST_DRIVER=centrifugo
- Tao token endpoint cho WebSocket authentication
- Tao 2 React hooks: useCentrifugoConnection va useCentrifugoSubscription
- Migrate dashboard polling sang WebSocket-backed cache invalidation
- Giu fallback polling (tang interval len 60s) khi WebSocket mat ket noi

**Non-Goals:**
- Full bidirectional WebSocket (chi server→client push)
- Them broadcast cho tat ca mutations (chi nhung endpoint da fire events)
- Thay doi Centrifugo config (dung config hien tai)
- Authentication cho WebSocket ngoai JWT token (khong can Sanctum)

## Decisions

### D1: Token Endpoint
**Decision:** Them `POST /api/centrifugo/token` tra ve JWT duoc sign boi Centrifugo HMAC secret. Khong can auth vi channel deu la public/universe-scoped.

### D2: Cache Invalidation Pattern
**Decision:** Khi nhan WebSocket message, invalidate React Query cache (queryClient.invalidateQueries) thay vi merge data truc tiep. Ly do: don gian, dung dung refetch logic da co, khong can parse message payload.

### D3: Graceful Degradation
**Decision:** useCentrifugoConnection tra ve connection state. Khi disconnected, dashboard tu dong bat lai polling (refetchInterval = 60s thay vi 10-15s). Khi reconnected, tat polling.

### D4: Channel Strategy
**Decision:** Subscribe 2 channels:
- `public:universes` — nhan broadcast moi khi bat ky universe nao pulse → invalidate ['universes'] queries
- `universes:{id}` — nhan broadcast cho universe cu the → invalidate metrics, snapshots, dossier queries

## Risks / Trade-offs

- **[Risk] Centrifugo chua chay trong Docker** → Mitigation: Fallback polling hoat dong binh thuong, WebSocket chi la optimization.
- **[Risk] JWT secret mismatch** → Mitigation: Doc tu config('centrifugo.hmac_secret'), same value dung trong CentrifugoBroadcaster.
