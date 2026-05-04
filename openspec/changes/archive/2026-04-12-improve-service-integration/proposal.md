## Why

Frontend co 10+ backend endpoints khong duoc goi (Knowledge/Wiki, generate-chronicle, analytics/ticks, mutation-chronicle, meaning-seeds). Narrative generation co backend endpoint nhung frontend khong trigger. Adaptive polling chi duoc implement cho 2 query (snapshots, forks) — con lai hardcode 15s interval bat ke WebSocket connected hay khong.

## What Changes

- **Narrative generation hook**: Frontend hook de trigger chronicle generation va poll task status
- **Centrifugo push cho narrative**: Backend broadcast khi narrative task hoan thanh
- **Knowledge/Wiki hooks**: Frontend hooks de goi wiki search, actor wiki, axioms
- **Adaptive polling**: Tat ca query hooks su dung `useAdaptiveRefetchInterval` thay vi hardcode

## Capabilities

### New Capabilities
- `narrative-trigger-ui`: Frontend hooks for triggering and tracking narrative generation
- `wiki-frontend-hooks`: Frontend hooks surfacing Knowledge/Wiki module data

### Modified Capabilities
- `realtime-broadcast`: Extend adaptive polling to all query hooks
- `websocket-dashboard`: Add narrative completion broadcast channel

## Impact

- `frontend/src/hooks/useChronicles.ts` — add generate mutation + adaptive polling
- `frontend/src/hooks/useWiki.ts` — new hooks for wiki search, actor wiki, axioms
- `frontend/src/hooks/useWavefunction.ts` — adaptive polling
- `frontend/src/hooks/useActors.ts` — adaptive polling
- `frontend/src/hooks/useMultiverse.ts` — adaptive polling
- `frontend/src/hooks/useAiLogs.ts` — adaptive polling
- `backend/app/Modules/Narrative/Services/NarrativeLoomService.php` — broadcast on completion
