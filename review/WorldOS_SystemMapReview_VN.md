# WorldOS / IPFactory - Code-First System Map Review

## Findings First

### 1. `simulation/advance` da duoc chuan hoa theo selected universe
- Frontend da chuan hoa payload advance sang `universe_id` va `ticks` trong [frontend/src/hooks/useSimulationControls.ts](C:/projects/IPFactory/frontend/src/hooks/useSimulationControls.ts:10).
- Backend validate va thuc thi cung contract `universe_id` + `ticks` trong [backend/app/Modules/WorldOS/Http/Controllers/UniverseController.php](C:/projects/IPFactory/backend/app/Modules/WorldOS/Http/Controllers/UniverseController.php:224).
- Tick advance UI hien gui ro selected universe thay vi payload mo ho.
- Muc do: `resolved`, contract frontend/backend da dong nhat.

### 2. Compare fork da duoc dua ve read semantics
- Frontend goi `GET /worldos/universes/{id}/forks/compare?branch_id=...` qua query hook trong [frontend/src/hooks/useSimulationControls.ts](C:/projects/IPFactory/frontend/src/hooks/useSimulationControls.ts:140).
- Backend chi khai bao `GET universes/{id}/forks/compare` trong [backend/app/Modules/WorldOS/routes/api.php](C:/projects/IPFactory/backend/app/Modules/WorldOS/routes/api.php:20).
- Test observer contract cung xac nhan endpoint nay duoc goi bang `GET` query string trong [backend/tests/Feature/Api/WorldosObserverContractTest.php](C:/projects/IPFactory/backend/tests/Feature/Api/WorldosObserverContractTest.php:164).
- Muc do: `resolved`, contract UI da theo read path cua backend.

### 3. Admin surface da duoc gom ve 2 route canonical
- Frontend admin data layer da chuyen sang `/apex/settings`, `/apex/settings/update`, `/apex/settings/reset` trong [frontend/src/features/admin/hooks/index.ts](C:/projects/IPFactory/frontend/src/features/admin/hooks/index.ts:81).
- Backend thuc te expose namespace `apex/settings*` trong [backend/app/Modules/Simulation/routes/api.php](C:/projects/IPFactory/backend/app/Modules/Simulation/routes/api.php:5).
- Frontend admin duoc gom lai thanh `/dashboard/system` va `/dashboard/ai-runtime`, khong con giu 4 route config cu song song.
- Muc do: `resolved`, control surface da ro hon va namespace da dung voi backend.

### 4. Frontend dang co hai lop access pattern song song, nhung app hien van nghi ve lop legacy
- Repo ton tai cap doi `src/hooks/*` va `src/features/*/hooks + api/queries` cho universe, simulation, actors, causal-map, wavefunction, multiverse, intelligence.
- App shell, dashboard pages, va phan lon component dang import tu `@/hooks/*`, vi du [frontend/src/contexts/UniverseContext.tsx](C:/projects/IPFactory/frontend/src/contexts/UniverseContext.tsx:4), [frontend/src/app/dashboard/page.tsx](C:/projects/IPFactory/frontend/src/app/dashboard/page.tsx:16), [frontend/src/components/dashboard/simulation/ForkPanel.tsx](C:/projects/IPFactory/frontend/src/components/dashboard/simulation/ForkPanel.tsx:15).
- Lop `features/*` hien giong huong refactor dang do hon la abstraction da duoc ung dung dong bo.
- Muc do: `medium`, vi no lam tang nguy co drift contract va duplicate logic polling/invalidation.

### 5. Tai lieu frontend da drift so voi code that
- Doc deep dive noi frontend dung "Vanilla CSS" va "Zustand" trong [docs/040-Modules/Frontend-Deep-Dive.md](C:/projects/IPFactory/docs/040-Modules/Frontend-Deep-Dive.md:1).
- Code hien tai dung React Query + React Context (`AuthProvider`, `UniverseProvider`) trong [frontend/src/components/Providers.tsx](C:/projects/IPFactory/frontend/src/components/Providers.tsx:1), [frontend/src/contexts/AuthContext.tsx](C:/projects/IPFactory/frontend/src/contexts/AuthContext.tsx:1), [frontend/src/contexts/UniverseContext.tsx](C:/projects/IPFactory/frontend/src/contexts/UniverseContext.tsx:1), va styling bang utility classes/Tailwind-like classnames tren khap `src/app`.
- Muc do: `medium`, vi drift tai lieu lam giam gia tri handoff va de dan den phan tich sai kien truc.

### 6. Module maturity giua docs va code khong dong deu
- `SocialGraph` route file gan nhu placeholder: "All SocialGraph universe routes moved to WorldOS" trong [backend/app/Modules/SocialGraph/routes/api.php](C:/projects/IPFactory/backend/app/Modules/SocialGraph/routes/api.php:1).
- `Knowledge` README noi module la placeholder, nhung route wiki van ton tai va dang co hook tieu thu trong [backend/app/Modules/Knowledge/README.md](C:/projects/IPFactory/backend/app/Modules/Knowledge/README.md:1), [backend/app/Modules/Knowledge/routes/api.php](C:/projects/IPFactory/backend/app/Modules/Knowledge/routes/api.php:1), [frontend/src/hooks/useWiki.ts](C:/projects/IPFactory/frontend/src/hooks/useWiki.ts:1).
- Muc do: `medium`, vi ranh gioi "implemented vs planned" khong ro neu chi doc module docs.

## Executive Summary

WorldOS/IPFactory hien la mot he thong 4 lop:

1. `frontend/` - Next.js observer console cho auth, dashboard, monitoring, narrative tools.
2. `backend/` - Laravel modular monolith, trong do `WorldOS` la API surface trung tam.
3. `backend/app/Modules/Simulation` - lop orchestration/runtime, snapshot, event pipeline, config, observer endpoints.
4. `engine/` - workspace Rust cho simulation core, gRPC/HTTP transport va rule evaluation.

Code-first cho thay he thong khong chi la "simulation platform", ma la mot `observer + control + narrative + AI config console` bao quanh universe lifecycle. Phan cohesive nhat hien nay la:
- universe-centric observer flows,
- dashboard shell + selected universe context,
- WorldOS public read APIs,
- simulation supervisor -> persistence -> event dispatch.

Phan fragmented nhat hien nay la:
- contract giua frontend va backend o mot so mutation,
- duplicate query layers trong frontend,
- maturity gap giua docs, README module, va route/controller thuc su.

## 1. System Map

```text
Observer
  |
  v
Next.js App Router
  - /login
  - /dashboard/*
  - /narrative-studio
  - /narrative-cinema/[chronicleId]
  |
  v
Axios + React Query + Context + Centrifugo client
  |
  +--> /api/auth/*
  +--> /api/worldos/*
  +--> /api/apex/*
  +--> /api/ai-*
  +--> /api/wiki/*
  +--> /api/loom-*
  |
  v
Laravel Modular Monolith
  - WorldOS module: public observer surface
  - Simulation module: orchestration/runtime + apex observer APIs
  - Intelligence module: auth, AI settings, key pool, logs
  - Narrative module: loom integration + chronicle services
  - Knowledge/SocialGraph/etc.: mixed maturity support modules
  |
  +--> DB / snapshots / models
  +--> Redis / cache / broadcast support
  +--> Centrifugo broadcaster
  +--> Narrative Loom service
  +--> Social engine / external services
  |
  v
Rust Engine Workspace
  - worldos-core
  - worldos-grpc
  - worldos-rules
```

## 2. What Exists

### 2.1 Frontend route groups and responsibilities

| Route group | Role in system | Main data sources |
| --- | --- | --- |
| `/` | Landing page, links vao dashboard va narrative tools | static |
| `/login` | Email/password auth entry | `/auth/login`, `/auth/me` |
| `/dashboard` | Dossier console cho universe dang active | `/worldos/universes`, `/metrics`, `/dossier` |
| `/dashboard/simulation` | Control panel cho tick, snapshot, fork, status | `/worldos/simulation/advance`, `/snapshots`, `/forks`, `/toggle-status` |
| `/dashboard/actors` | Actor registry + modal detail | `/worldos/universes/{id}/actors`, `/worldos/actors/*` |
| `/dashboard/causal-map` | Spatial topology + manual causal link fetch | `/apex/v10/universes/{id}/topology`, `/worldos/universes/{id}/causal-links` |
| `/dashboard/wavefunction` | Monitoring quantum/field diagnostics | `/apex/wavefunction/*`, `/apex/v10/universes/{id}/*` |
| `/dashboard/multiverse` | Multiverse tree + resonance feed | `/apex/multiverse/bloom`, `/apex/multiverse/resonance` |
| `/dashboard/intelligence/monitor` | AI logs/stats monitor | `/ai-logs`, `/ai-logs/stats`, `/ai-settings` |
| `/dashboard/system` | Canonical system runtime page for simulation config + service health | `/apex/settings*`, `/worldos/service-status` |
| `/dashboard/ai-runtime` | Canonical AI runtime page for routing, diagnostics, loom agents, provider models, key pool | `/ai-settings*`, `/ai-settings/diagnostics`, `/ai-provider-models`, `/ai-key-pool` |
| `/narrative-studio` | Narrative Loom live monitor + trigger generate | `/loom-status`, `/worldos/universes/{id}/generate-chronicle`, Centrifugo |
| `/narrative-cinema/[chronicleId]` | Chronicle playback / cinematic fallback | `/worldos/chronicles/{id}` |

### 2.2 Backend public interfaces

#### `auth` and AI/intelligence namespace
- `POST /api/auth/login`, `POST /api/auth/register`
- protected: `POST /api/auth/logout`, `GET /api/auth/me`
- public reads for `ai-settings`, `ai-key-pool`, `ai-provider-models`, `ai-logs`
- protected writes for settings sync/import/update and model/key CRUD

#### `worldos` namespace
- Public read-heavy observer API:
  - universes, metrics, dossier, snapshots, forks, reality-state
  - chronicles, myth-scars, artifacts, history-timeline, causal-links
  - actors, actor events/decisions, supreme entities
  - service-status, analytics, config reads, centrifugo token
- Protected mutations:
  - create/update/delete/toggle universe
  - create snapshot, fork, advance simulation, pulse world
  - generate history, raw chronicles, generate chronicle
  - actor mind-meld
  - config writes

#### `apex` namespace
- Observer/analytics surfaces around simulation internals:
  - wavefunction
  - informational mass
  - mutation chronicle
  - v10 state-at, delta, topology, consciousness, ascension-filters
  - multiverse bloom/resonance
  - settings

#### `wiki`, `loom-*`, and `social-graph`
- `wiki/*` is real and queryable.
- `loom-status` and internal loom API routes are real.
- `social-graph/*` currently has no concrete public endpoints in module route file.

### 2.3 Rust engine workspace

| Package | Observed role |
| --- | --- |
| `engine/worldos-core` | Core simulation primitives and systems |
| `engine/worldos-grpc` | Transport layer exposing gRPC + HTTP server |
| `engine/worldos-rules` | DSL parse/evaluate rule engine |

Notable detail:
- `server.rs` starts gRPC on `50051` and HTTP on `50052` in [engine/worldos-grpc/src/bin/server.rs](C:/projects/IPFactory/engine/worldos-grpc/src/bin/server.rs:1).
- Laravel binding can use gRPC when extension exists, otherwise falls back to HTTP in [backend/app/Providers/AppServiceProvider.php](C:/projects/IPFactory/backend/app/Providers/AppServiceProvider.php:24).
- Default config value is named `simulation_engine_grpc_url` but defaults to `http://engine:50052` in [backend/config/worldos_simulation.php](C:/projects/IPFactory/backend/config/worldos_simulation.php:1), so the default path is effectively HTTP transport, not gRPC.

## 3. Dataflow View

### 3.1 Auth and dashboard bootstrap

```text
RootLayout
  -> Providers
    -> QueryClientProvider
    -> AuthProvider
      -> restore token from localStorage
      -> GET /auth/me
DashboardLayout
  -> DashboardShell
    -> AuthGuard
      -> redirect /login if unauthenticated
    -> UniverseProvider
      -> GET /worldos/universes
    -> useRealtimeSync
      -> Centrifugo token + connection
```

Observed behavior:
- Auth is client-side token based via localStorage and axios default header.
- Dashboard bootstrap depends on `AuthProvider` then `UniverseProvider`.
- Selected universe is shared via React Context, not a global state store.

### 3.2 Active universe -> dossier and metrics

```text
Sidebar universe selector
  -> UniverseContext.selectedUniverseId
  -> activeUniverseId
  -> dashboard pages read same context
     -> useUniverseMetrics(activeUniverseId)
     -> useUniverseDossier(activeUniverseId)
  -> backend WorldOS services
     -> UniverseMetricsService
     -> UniverseDossierService
     -> projectors + latest snapshot + counts
```

Observed behavior:
- Universe list polling is every 15s by default.
- Metrics/dossier polling is every 10s in legacy hooks.
- Dossier is synthesized read-model style data, not raw universe rows.

### 3.3 Simulation control

Intended flow:

```text
Simulation page
  -> TickAdvancePanel / SnapshotPanel / ForkPanel / UniverseStatusPanel
  -> mutation hooks
  -> WorldOS protected endpoints
  -> UniverseController
  -> Simulation actions / fork action / repository writes
  -> invalidate universe queries
```

Actual notes:
- Snapshot create/list and fork create/list line up reasonably.
- Advance and compare-fork currently have verified frontend/backend contract drift.
- Universe status panel reads metrics from dossier hooks and mutations from simulation hooks.

### 3.4 Narrative and actor exploration

Actors:

```text
/dashboard/actors
  -> useActors(activeUniverseId)
  -> GET /worldos/universes/{id}/actors
  -> ActorController@index
  -> GetUniverseActorsAction
```

Narrative:

```text
/dashboard + /narrative-cinema
  -> useChronicles / useChronicleDetail / useMythScars / useArtifacts
  -> NarrativeController and TimelineController
  -> repositories + Chronicle model
```

Loom orchestration:

```text
/narrative-studio
  -> GET /loom-status
  -> POST /worldos/universes/{id}/generate-chronicle
  -> TimelineController@generateChronicle
  -> NarrativeLoomService::weave(...)
  -> webhook /worldos/narrative-loom/webhook
  -> Chronicle upsert + Centrifugo broadcast
```

### 3.5 Realtime sync and cache invalidation

```text
frontend getCentrifuge()
  -> POST /worldos/centrifugo/token
  -> ws connect
  -> subscribe public:universes
  -> subscribe universes:{activeUniverseId}
  -> React Query invalidate ['universes'] and ['universes', id]
```

Observed behavior:
- Realtime layer mostly does invalidation, not direct state patching.
- Hooks often combine WebSocket-aware fallback polling with React Query.
- Narrative Studio subscribes to task-specific channels separately from dashboard invalidation.

## 4. Capability Maturity Matrix

| Area | Maturity | Notes |
| --- | --- | --- |
| Auth bootstrap | Implemented in code | Login, token restore, auth guard present |
| Universe list / selection / dossier / metrics | Implemented in code | Strongest and most cohesive observer flow |
| Snapshots and fork list/create | Implemented in code | Core UI + WorldOS API exist |
| Advance simulation | Implemented in code | UI/backend contract da dong nhat tren `universe_id` + `ticks` |
| Fork compare | Implemented in code | UI su dung GET query path giong observer contract |
| Actors browse/detail/events/decisions | Implemented in code | Clear route-hook-controller path |
| Wavefunction / topology / multiverse monitoring | Implemented in code | `apex` read namespace is real |
| Simulation settings center | Implemented in code | Frontend admin data layer su dung `apex/settings*` |
| Key pool / AI settings / AI logs | Implemented in code | Intelligence module has concrete CRUD and monitors |
| Narrative Studio + Loom | Implemented in code, integration-heavy | Real routes and websocket flow exist; relies on external service |
| Narrative Cinema | Implemented in code | Chronicle detail + animation fallback |
| Knowledge wiki | Implemented but peripheral | Real endpoints exist despite placeholder README |
| SocialGraph public API | Placeholder / docs-led | Module routes empty, functionality mainly event/listener side |
| Rust engine integration | Implemented in code | Real binding, HTTP/gRPC transport, supervisor orchestration |
| Frontend feature-query architecture | Partial / transitional | Exists, but app still mostly consumes legacy hooks |
| Frontend test coverage | Weak / absent in app code | No app-level frontend tests found outside `node_modules` |

## 5. What Is Central vs Peripheral

### Central
- `WorldOS` module as observer-facing API facade.
- `UniverseContext` as app-wide selected-universe pivot.
- `SimulationSupervisor` as orchestration spine after an advance request.
- `UniverseMetricsService` and `UniverseDossierService` as synthesized read models.
- `React Query + Axios + Centrifugo invalidation` as frontend data plane.

### Peripheral or specialized
- `Knowledge/wiki` endpoints.
- `Narrative Cinema` VAF playback.
- `SocialGraph` public routes.
- `sim/social-engine` Python package in current repo state.
- Legacy/duplicate frontend query abstractions under `features/*`.

## 6. Cohesive vs Fragmented

### Cohesive areas
- Universe-centric dashboard experience.
- Read-side APIs for dossier/metrics/actors/chronicles.
- Runtime architecture where Laravel orchestrates and Rust computes.
- Service-status and monitoring surfaces that reflect external dependency topology.

### Fragmented areas
- Mutation contracts not uniformly enforced between UI and backend.
- Namespace sprawl: `worldos`, `apex`, `auth`, `ai-*`, `wiki`, `loom-*`.
- Documentation parity: several docs still describe older state management and frontend stack choices.
- Frontend architecture straddles old hooks and newer feature-query wrappers.

## 7. Docs Parity Notes

### Confirmed by code
- Overall 4-layer shape: frontend -> Laravel -> engine -> infra/services.
- Centrifugo-based realtime intent.
- Modular monolith backend organization.

### Drift from code
- Frontend deep dive says Zustand + Vanilla CSS, but code uses Context + React Query + utility-class styling.
- System docs describe SocialGraph/Neo4j as a more visible surface than route inventory currently shows.
- Knowledge README says placeholder, while wiki routes are active and consumable.

## 8. Bottom Line

Neu xem bang code thay vi docs, WorldOS hien tai la:
- mot observer console da co nhieu read flows chay duoc,
- mot backend facade xoay quanh `worldos` va `apex`,
- mot simulation runtime ma Laravel dieu phoi con Rust tinh toan,
- mot he thong narrative/AI config duoc tich hop kha sau vao UI.

Rui ro lon nhat hien tai khong nam o viec "thieu module", ma nam o viec mot so contract giua frontend va backend da bi lech trong khi repo van mang dong thoi:
- docs cu,
- abstraction cu,
- abstraction moi,
- va service integration ben ngoai.

Do do, neu dung tai lieu nay de handoff hoac audit tiep, nen xem `WorldOS routes + frontend hooks/pages + SimulationSupervisor/AppServiceProvider` la source of truth chinh.
