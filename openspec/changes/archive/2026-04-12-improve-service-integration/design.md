## Context

Frontend dung React Query voi hardcoded refetchInterval (5-15s). useRealtimeSync da co nhung chi invalidate query cache — cac hooks van poll doc lap. useAdaptiveRefetchInterval da duoc tao (return false khi WS connected, fallback khi disconnected) nhung chi 2/12 hooks dung no. Wiki module co 5 routes nhung 0 frontend hooks.

## Goals / Non-Goals

**Goals:**
- Tao useWiki hooks cho Knowledge module (search, actor-wiki, axioms)
- Them generate-chronicle mutation vao useChronicles
- Broadcast narrative completion qua Centrifugo
- Ap dung adaptive polling cho tat ca query hooks

**Non-Goals:**
- Tao UI components (chi hooks layer)
- Modify NarrativeLoomService internals
- Add Kafka consumers for narrative events

## Decisions

### D1: Wiki Hooks
**Decision:** Tao `useWiki.ts` voi 3 hooks: `useWikiSearch(universeId, query)`, `useActorWiki(universeId, actorId)`, `useAxioms()`. Lazy fetch (enabled only when params present).

### D2: Generate Chronicle
**Decision:** Them `useGenerateChronicle()` mutation hook vao useChronicles.ts. On success invalidate chronicles query. Backend broadcast `public:universes` khi weave xong.

### D3: Adaptive Polling Adoption
**Decision:** Import `useAdaptiveRefetchInterval` tu useCentrifugo vao moi hook file. Replace hardcoded refetchInterval voi adaptive value. Default fallback 15s cho data-heavy, 30s cho less critical.

## Risks / Trade-offs

- **[Risk] Wiki search co the slow** → Mitigation: staleTime 30s, enabled only when query length > 2
- **[Risk] Broadcast failure khi Centrifugo down** → Mitigation: Non-blocking, try/catch in service
