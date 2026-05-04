## 1. Backend — Enable Broadcasting

- [x] 1.1 Update `.env.example` to set BROADCAST_DRIVER=centrifugo
- [x] 1.2 Create `CentrifugoTokenController` with token generation endpoint
- [x] 1.3 Register route `POST /api/centrifugo/token` in WorldOS routes

## 2. Frontend — WebSocket Hooks

- [x] 2.1 Create `useCentrifugoConnection` hook — connection lifecycle, state, auto-reconnect
- [x] 2.2 Create `useCentrifugoSubscription` hook — channel subscription with cache invalidation callback

## 3. Frontend — Dashboard Migration

- [x] 3.1 Update simulation queries to use WebSocket-backed refetchInterval (60s fallback vs disabled when connected)
- [x] 3.2 Update universe queries to use WebSocket-backed refetchInterval
- [x] 3.3 Add CentrifugoProvider at dashboard layout level

## 4. Cleanup

- [x] 4.1 Update `.dev_status.md`
