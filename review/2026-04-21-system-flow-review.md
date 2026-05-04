# WorldOS V6 — System Flow & Data Exchange Review

> **Ngày review:** 2026-04-21
> **Reviewer:** Antigravity AI (Claude Sonnet 4.6 Thinking)
> **Phạm vi:** Toàn bộ hệ thống — Backend (Laravel 12), Simulation Engine (Rust gRPC), Narrative Loom (Python/FastAPI/LangGraph), Frontend (Next.js 16), Infrastructure (Redis, Centrifugo, Celery)

---

## 1. Kiến Trúc Tổng Thể

### 1.1 Sơ Đồ 5 Lớp

```
┌─────────────────────────────────────────────────────────────────────┐
│  LAYER 1: FRONTEND (Next.js 16 / React 19 / TypeScript)             │
│  frontend/src/                                                       │
│  - /features/narrative-runtime   (Narrative Loom UI)                │
│  - /features/simulation          (Simulation Dashboard)             │
│  - /features/universe            (Universe Management)              │
│  - /features/intelligence        (Actor Intelligence)               │
│  - /features/wavefunction        (Apex Observer)                    │
│  Realtime: Centrifugo WebSocket (centrifuge-js)                     │
└───────────────────┬─────────────────────────┬───────────────────────┘
                    │ HTTP (REST/JSON)         │ WebSocket
                    ▼                         ▼
┌─────────────────────────────────────────────────────────────────────┐
│  LAYER 2: API GATEWAY (Nginx + Laravel 12 API)                      │
│  backend/app/Modules/                                               │
│  ├── Simulation/    — WorldOS simulation lifecycle                  │
│  ├── Intelligence/  — Actor AI decisions, LLM key pool              │
│  ├── Narrative/     — Chronicle generation, lore pipeline           │
│  ├── WorldOS/       — Universe management CRUD                      │
│  ├── World/         — World configuration                           │
│  └── SocialGraph/   — Relationship graphs                           │
└───────────────────┬─────────────────┬───────────────────────────────┘
                    │ gRPC             │ HTTP
                    ▼                 ▼
┌──────────────────────┐  ┌──────────────────────────────────────────┐
│  LAYER 3a:           │  │  LAYER 3b:                               │
│  RUST ENGINE         │  │  NARRATIVE LOOM (Python FastAPI)         │
│  engine:50052        │  │  narrative_loom:8001                     │
│                      │  │                                          │
│  Physics / DSL /     │  │  FastAPI + LangGraph + Celery            │
│  Mass Actor compute  │  │  16 AI Agents / 7 Engines                │
│                      │  │  Tasks: chronicle, actor-intent          │
└──────────────────────┘  └──────────────────────────────────────────┘
                    │                         │
                    ▼                         ▼
┌─────────────────────────────────────────────────────────────────────┐
│  LAYER 4: SHARED INFRASTRUCTURE                                     │
│  ├── Redis          — State cache, Celery broker, LLM cache         │
│  ├── Centrifugo     — WebSocket pub/sub gateway (port 8000)         │
│  └── PostgreSQL     — Persistent storage                            │
└─────────────────────────────────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│  LAYER 5: EXTERNAL LLM PROVIDERS                                    │
│  OpenAI / Anthropic / Google / OpenRouter / ZAI / Local LLM        │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 2. Luồng Simulation Per-Tick (Backend Laravel → Rust Engine)

### 2.1 Sequence Per-Tick Chi Tiết

```
Artisan Command / REST API
        │
        ▼
AdvanceSimulationAction::execute($universeId, $ticks)
        │
        ▼
SimulationSupervisor (main loop)
        │
        ├── Load Universe from DB
        │
        ▼
SimulationTickPipeline::run($universe, $tick)
        │
        ├── 1. StateManager::load($universe, $snapshot)
        │        └─► Redis cache → DB fallback
        │        └─► WorldState hydration (zones, agents, institutions)
        │
        ├── 2. WorldKernel::execute($state, $tick)
        │        │
        │        ├─ Phase 0: AgentBatchProcessor::executeAgentActions()
        │        │        └─► state->syncAgentsToZones()
        │        │
        │        ├─ Phase ENVIRONMENT → LIFE → MIND → SOCIAL → META
        │        │        │
        │        │        ├─ Legacy: PhaseExecutor (registerSystem())
        │        │        │
        │        │        └─ v2: PhaseRegistry Engines (AbstractWorldOSEngine)
        │        │               ├─ Physics: ClimateEngine, GeologicalEngine,
        │        │               │           MaterialEvolutionEngine, MetabolicEngine,
        │        │               │           CosmicPressureEngine, StructuralDecayEngine
        │        │               ├─ Biological: AutopoieticEvolutionEngine
        │        │               ├─ Social: CultureEngine, IdeaDiffusionEngine
        │        │               └─ Meta: MythogenesisEngine, KnowledgeEvolutionEngine,
        │        │                        PowerStructureEngine, AscensionEngine
        │        │
        │        ├─ StateTransitionEngine::run() (ISTE - Global Emergence)
        │        │
        │        └─ TickFinalizer::processCausalImpacts() + finalizeTick()
        │
        ├── 3. ZenithMetricsService::getZenithReport($state)
        │
        ├── 4. StateManager::save($universe)
        │        └─► Redis + DB snapshot
        │
        ├── 5. SnapshotManager: INSERT universe_snapshots (tick, state_vector, metrics)
        │
        └── 6. NarrativeEngine::pulse($universeEntity, $snapshot)
                 └─► [Conditional] → NarrativeLoomService::weave()
```

### 2.2 WorldKernel — 5 Pha Thực Thi

| Pha | Key | Engines Chính |
|-----|-----|---------------|
| ENVIRONMENT | `environment` | ClimateEngine, GeologicalEngine, MaterialEvolutionEngine, MetabolicEngine |
| LIFE | `life` | AutopoieticEvolutionEngine, EvolutionPressureService |
| MIND | `mind` | CognitiveDynamicsEngine, DecisionEngine, ActorBehaviorEngine |
| SOCIAL | `social` | CultureEngine, IdeaDiffusionEngine, InstitutionEngine, DiplomaticEngine |
| META | `meta` | MythogenesisEngine, KnowledgeEvolutionEngine, AscensionEngine, PowerStructureEngine |

### 2.3 Data Contract: WorldState

```
WorldState {
  universe_id: int
  tick: int
  tech_level: float
  entropy: float
  stability_index: float
  myth_intensity: float
  zones[]: {
    id, name,
    state: {
      minerals, temperature, rainfall, biome, terrain_type,
      available_materials, material_profile,
      agents[], institutions[]
    }
  }
  agents[]: { id, name, archetype, traits[], biography, zone_id }
  institutions[]: { id, type, influence, policies[] }
  meta: { zenith: {...metrics} }
}
```

### 2.4 MaterialEvolutionEngine — Ví Dụ Engine Chi Tiết

- **Phase:** `physics`, priority = 4
- **Tick rate:** Mỗi 10 tick
- **Input:** `WorldState.zones[].state.minerals`, `WorldState.tech_level`
- **Output:** `zones[].state.available_materials`, `zones[].state.material_profile`
- **Events emitted:** `material_unlocked` (khi tier mới mở khóa)

```
tech_level → Material Tier Unlock:
  0.0 → stone
  0.1 → copper
  0.2 → bronze
  0.3 → iron
  0.5 → steel
  0.7 → alloy
  0.9 → advanced
```

---

## 3. Narrative Loom Pipeline (Python FastAPI / LangGraph / Celery)

### 3.1 Kiến Trúc Service

```
narrative-loom/ (FastAPI v2.0.0 — port 8001)
├── main.py              — App entry, mounts 4 routers
├── routers/
│   ├── chronicle.py     — POST /weave-chronicles, GET /tasks/{id}/status
│   ├── actors.py        — POST /actor-intent
│   ├── scribe.py        — POST /scribe-history
│   └── system.py        — GET /health, /config, /metrics
├── graph.py / graph_builder.py  — LangGraph StateGraph (18 nodes)
├── state.py             — NarrativeState TypedDict (shared pipeline state)
├── agents/              — 16 AI agents
├── engines/             — 7 analytic engines (no LLM)
├── tasks/               — Celery tasks (chronicle_task.py)
├── core/
│   ├── centrifugo.py    — Pub/sub WebSocket events
│   ├── celery_app.py    — Queue: "narrative"
│   ├── metrics.py       — MetricsCollector singleton
│   └── logging.py       — structlog setup
└── utils/
    └── llm_factory.py   — LLM routing (OpenAI/Anthropic/Google/Local/...)
```

### 3.2 LangGraph Pipeline — 18 Nodes Tuần Tự

```
[Input: raw_chronicles từ Laravel]
  │
  ▼
Event_Normalizer
  → Universe_Bridge
  → Entropy_Engine
  → Attractor_Engine
  → Style_Analyzer
  → Dramatic_Arc
  → Phase_Engine
  → Singularity_Engine
  → Chief_Editor
  → The_Historian
  → The_Mythologist
  → The_Psychologist
  → The_Director
  → The_Wordsmith
  → The_Critic ─── [pass / max 2 retries] ──► The_Archivist
        │                                            │
        └─── [fail → retry The_Wordsmith]      News_Anchor
                                                     │
                                               VFX_Director
                                                     │
                                                   [END]
```

#### 7 Analytic Engines (Pure Python, không dùng LLM)

| Engine | Output vào NarrativeState |
|--------|--------------------------|
| `event_normalizer` | `normalized_events`, `filtered_events` |
| `entropy_engine` | `event_scores`, `epistemic_noise`, `epistemic_tier` |
| `attractor_engine` | `attractor_clusters`, `attractor_strength` |
| `arc_engine` | `dramatic_arc` |
| `phase_engine` | `narrative_phase`, `phase_score` |
| `singularity_engine` | `singularity` |
| `style_analyzer` | `genre`, `style_guidelines` |

#### 10 AI Agents (LLM-powered)

| Agent | Role | Output chính |
|-------|------|-------------|
| `chief_editor` | Content orchestration | Direction context |
| `historian` | Historical outline | `historical_outline` |
| `mythologist` | Mythological context | Myth layer |
| `psychologist` | Psychological profiles | `psychological_profiles` |
| `director` | Scene direction / Storyboard | `storyboard` |
| `wordsmith` | Literary prose | `final_prose` |
| `critic` | Quality review | `feedback` (pass/fail + reasoning) |
| `archivist` | Content persistence | Saved to DB |
| `news_anchor` | News headlines | `news_headline`, `news_slogan` |
| `vfx_director` | Visual effects config | `vfx_config`, `animation_script` |

### 3.3 Chronicle Weave — Luồng Dữ Liệu Đầy Đủ

```
[Frontend]
  POST /api/worldos/universes/{id}/generate-chronicle
            │
            ▼
[Laravel] NarrativeEngine::pulse()
            └─► NarrativeLoomService::weave()
                    │
                    ▼
[Python] POST /weave-chronicles
  payload: {
    world_id, world_era, tick_start, tick_end,
    genre, power_system, whispers[], ai_runtime: { provider, model, api_key }
  }
            │
            ├── 1. Fetch chronicles từ Laravel:
            │        GET /loom/v1/narrative/chronicles?world_id=&tick_start=&tick_end=
            │
            ├── 2. Build NarrativeState:
            │        raw_chronicles, era_context, power_manifesto,
            │        vfx_hints, task_id, completed_agents=[]
            │
            ├── 3. Celery dispatch:
            │        weave_chronicle_task.apply_async(
            │          args=[initial_state, world_id, task_id],
            │          queue="narrative", soft_time_limit=1800s
            │        )
            │
            └── 4. Return immediately:
                    { task_id, world_id, channel: "narrative:{world_id}:{task_id}" }

[Celery Worker — async]
  weave_chronicle_task()
    ├── publish_pipeline_started(world_id, task_id, total_agents=18)
    │         → Centrifugo: channel narrative:{world_id}:{task_id}
    │
    ├── LangGraph ainvoke(initial_state) [async, 18 nodes]
    │         Mỗi agent: publish_agent_started / publish_agent_done / publish_agent_error
    │
    ├── publish_pipeline_done(world_id, task_id, result)
    │         → Centrifugo: { final_prose, news_headline, storyboard, vfx_config }
    │
    └── POST /api/worldos/narrative-loom/webhook (Laravel callback)
              { type, task_id, world_id, tick_start, tick_end, ...pipeline_result }
```

### 3.4 Actor Intent Flow

```
[WorldKernel] AgentBatchProcessor → ActorBehaviorEngine
        │
        ▼
[Laravel] LoomIntentClient::requestIntent(ActorEntity, UniverseContext)
  Request payload: {
    actor_id, actor_name, archetype,
    traits: { openness, conscientiousness, ... },
    universe_context: { entropy, stability_index, myth_intensity, tick },
    recent_biography: string (cuối 5 dòng),
    available_actions: [revolt, form_contract, migrate, trade,
                        suppress_revolt, propagate_myth],
    provider, model_name?, api_key?, base_url?
  }
        │
        ▼ POST /actor-intent (timeout=120s)
[Python] intent_agent.py
        └─► LLM reasoning (via llm_factory)
        └─► Response: { action: string, confidence: float, reasoning: string }
        │
        ▼ [Fallback chain]
[Laravel] IntentResponse::fromArray() → isReliable() (confidence >= 0.6)
  ├── IS reliable → IntentActionMapper → execute action (RevoltAction, TradeAction, ...)
  └── NOT reliable / timeout / error → DecisionEngine (rule-based fallback)
```

---

## 4. Intelligence Module — AI Key Pool & Gateway

### 4.1 Luồng Chọn Provider LLM

```
AiGateway::runtimeProfileForFeature('narrative' | 'decision')
  │
  ├── Query AiKeyPool table:
  │     WHERE feature_scope = ?
  │     AND is_active = true
  │     AND NOT rate-limited
  │     ORDER BY priority, last_used_at ASC
  │
  ├── Rotate key (circuit breaker per key)
  │
  └── Return runtime: {
        provider: 'openai' | 'anthropic' | 'google' | 'local' | 'openrouter' | 'zai',
        model: string,
        api_key: string,
        key_entry: AiKeyPool  # for usage reporting
      }
```

### 4.2 LLM Factory Priority Chain (narrative-loom)

```
1. ai_runtime từ request (provider + api_key gửi trực tiếp từ Laravel)
        │
        ▼  [nếu không có]
2. GET /ai-settings/loom-agents (Laravel DB) → tìm agent config
        │
        ▼  [nếu thất bại]
3. File fallback: configs/agent_routing.json → routing config
        │
        ▼  [nếu thất bại]
4. get_llm(provider="local") — Local LLM (không cần API key)
```

**Providers được hỗ trợ:**

| Provider | Default Model | Notes |
|----------|--------------|-------|
| `openai` | gpt-4o | Standard OpenAI |
| `anthropic` | claude-3-opus-20240229 | Anthropic API |
| `google` | gemini-1.5-pro-latest | Google GenAI |
| `openrouter` | google/gemini-flash-1.5 | Multi-model proxy |
| `zai` | GLM-4.5-Flash | ZAI API |
| `alibaba/qwen` | qwen-max | DashScope |
| `local` | qwen3.5-9b-... | Local LLM URL |

**Cache:** `TickBasedCache` — 80-tick lifespan per (world_id, tick, provider, prompt)

---

## 5. Real-time Communication — Centrifugo

### 5.1 Channel Convention

| Event source | Channel format | Subscriber |
|-------------|---------------|------------|
| Narrative Loom pipeline v2 | `narrative:{world_id}:{task_id}` | `useNarrativeRuntime` |
| Legacy (deprecated) | `universe.{id}.narrative` | Legacy components |

### 5.2 Event Types (Centrifugo messages)

| `type` | Payload fields | Frontend action |
|--------|---------------|----------------|
| `pipeline_started` | `total_agents` | `isWeaving=true`, reset progress |
| `agent_started` | `agent`, `input?`, `stage?` | Node → "running" |
| `agent_done` | `agent`, `duration_ms`, `progress.{completed,total,pct}` | Node → "completed" |
| `agent_error` | `agent`, `error` | Node → "error", `lastError` |
| `pipeline_progress` | `completed`, `total`, `pct` | Progress bar update |
| `pipeline_done` | `task_id`, `final_prose`, `news_headline`, `news_slogan`, `storyboard`, `vfx_config`, `completed_agents[]` | Finalize result |
| `pipeline_error` | `error` | Stop, show error |

### 5.3 Frontend Fallback — HTTP Polling

Khi Centrifugo offline, `useNarrativeRuntime` fallback polling:

```
[Every 5 seconds]
GET /loom-tasks/{task_id}/status
  → Laravel proxy → Celery AsyncResult (Redis backend)
  → Response: { task_id, status: PENDING|SUCCESS|FAILURE, result? }

[On SUCCESS]  → finalizeTaskFromResult(result)
[On FAILURE]  → setLastError(message), clearTrackedSession()
```

---

## 6. Frontend — Features và API Contracts

### 6.1 Narrative Runtime Hook (`useNarrativeRuntime`)

**API calls:**

| Method | Endpoint | Tần suất |
|--------|---------|---------|
| `GET /loom-status` | Loom health + agent config | Mỗi 30 giây |
| `POST /worldos/universes/{id}/generate-chronicle` | Khởi động chronicle | On demand |
| `GET /loom-tasks/{task_id}/status` | Poll task | Mỗi 5s (fallback) |

**State tree:**

```typescript
{
  loomStatus: {
    status: 'online' | 'offline' | 'degraded' | 'error',
    agents: Record<string, { provider, model, tier, role }>,
    providers: Record<string, { status, key_present }>,
    version?: string
  },
  isWeaving: boolean,
  activeTaskId: string | null,
  worldId: number | null,
  pipelineNodes: Record<string, {
    status: 'idle' | 'running' | 'completed' | 'error',
    startedAt?: number,
    completedAt?: number,
    durationMs?: number,
    error?: string
  }>,
  progress: { completed: number, total: number, pct: number },
  narrativeResult: { headline?, prose?, newsSlogan? } | null,
  intermediateOutputs: { historical_outline?, storyboard?, final_prose?, vfx_config? },
  logs: string[],           // max 120 entries
  connectionState: 'connected' | 'disconnected',
  isRestoredSession: boolean // restored từ localStorage
}
```

**Session persistence:**
- `localStorage["worldos:narrative-runtime:session"]` = `{ taskId, worldId }`
- Tự động restore và re-subscribe Centrifugo khi reload trang

### 6.2 Simulation API Endpoints (`/api/apex/`)

| Endpoint | Controller | Purpose |
|----------|-----------|---------|
| `GET /apex/wavefunction/{universeId}` | ApexObserverController | Quantum state projection |
| `GET /apex/informational-mass/{universeId}` | ApexObserverController | Information metrics |
| `GET /apex/mutation-chronicle/{universeId}` | ApexObserverController | DSL mutation history |
| `GET /apex/v10/universes/{id}/state-at/{tick}` | ApexObserverController | Historical state replay |
| `GET /apex/v10/universes/{id}/delta` | ApexObserverController | State diff between ticks |
| `GET /apex/v10/universes/{id}/topology` | ApexObserverController | Universe topology graph |
| `GET /apex/v10/universes/{id}/consciousness` | ApexObserverController | Consciousness field |
| `GET /apex/v10/universes/{id}/ascension-filters` | ApexObserverController | Ascension status |
| `GET /apex/multiverse/bloom` | MultiverseMapController | Multiverse DAG |
| `GET /apex/multiverse/resonance` | MultiverseMapController | Resonance map |
| `GET /apex/settings` | SimulationSettingsController | Dynamic config |
| `POST /apex/settings/update` | SimulationSettingsController | Update config (auth) |

---

## 7. Backend Modules — Dependency Map

### 7.1 Module Interaction Graph

```
WorldOS Module
  └─► Universe CRUD, World management
      ├─► triggers: Simulation Module (AdvanceSimulationAction)
      └─► triggers: Narrative Module (chronicle endpoints)

Simulation Module
  ├─► WorldKernel (5 phases, 15 rules, 20+ engines)
  │     ├─► Intelligence Module (actor decisions)
  │     └─► [optional] Rust gRPC (engine:50052)
  ├─► Intelligence Module
  │     ├─► LoomIntentClient → POST /actor-intent (Narrative Loom)
  │     ├─► AiGateway (key pool management)
  │     └─► ActorBehaviorEngine (per-actor decision cycle)
  └─► Narrative Module
        └─► NarrativeEngine::pulse() → NarrativeLoomService::weave()

Intelligence Module
  Actions: (23 actions)
    ProcessActorEnergyAction, ProcessActorSurvivalAction,
    RunMicroCycleAction, SpawnActorAction, UniverseForkAction,
    RevoltAction, MigrateAction, FormContractAction, ...

  Services: (48+ services)
    ActorBehaviorEngine, AgentAutonomyService,
    MacroAgentEngine, CivilizationAttractorEngine,
    IdeaDiffusionEngine, CultureEngine,
    LoomIntentClient (→ Narrative Loom),
    AiGateway (→ LLM providers)

Narrative Module
  Services: (44 services)
    NarrativeLoomService (→ Python),
    NarrativeEngine (tick-based pulse),
    MythologyEngine, ReligionGenerator,
    ChapterGenerator, SerialStoryService,
    OmenIntegrationService
```

### 7.2 Event Flow (Laravel Events → Listeners)

| Event | Phát ra tại | Listeners |
|-------|------------|----------|
| `SimulationTickCompleted` | SimulationSupervisor | NarrativeListener, MetricsListener |
| `material_unlocked` | MaterialEvolutionEngine | Achievement unlock, Chronicle trigger |
| `AscensionThresholdReached` | Meta AscensionEngine | Universe evolution |
| `NarrativeWeavingComplete` | Narrative Webhook | DB archive, Centrifugo broadcast |
| `ActorRevolt` | RevoltAction | Social graph update, history event |
| `UniverseFork` | UniverseForkAction | Multiverse branch creation |

---

## 8. Database Schema (Simplified)

### 8.1 Tables Chính

```sql
universes
  id, world_id (FK), current_tick (int), status (active|halted|archived),
  state_vector (JSON), entropy (float)

universe_snapshots
  id, universe_id (FK), tick (int), state_vector (JSON large), 
  metrics (JSON: { entropy, stability, engine_health, last_tick_ms })
  -- 1 row per tick = 10,000 rows cho 10,000-tick run

worlds
  id, civilization_era, base_genre, current_genre, power_system_type

narratives
  id, universe_id, story (text), virality (float), is_active (bool), tick_created

ai_key_pools
  id, provider, feature_scope, api_key (encrypted), is_active,
  usage_count, last_error_at, error_code

ai_logs
  id, feature, driver, model, input (JSON), output (JSON),
  latency_ms (int), status (success|error), error_message
```

### 8.2 Redis Keys (Cache Patterns)

| Key pattern | Nội dung | TTL |
|-------------|---------|-----|
| `worldos:state:{universe_id}` | WorldState JSON | Rolling update |
| `v1:{provider}:{llm_string}:{prompt}` | LLM response (TickBasedCache) | 80 ticks |
| `celery-task-{task_id}` | Celery result | Config-based |
| `circuit_breaker:narrative_loom` | Failure count (3 = open, 60s reset) | 60s |
| Centrifugo presence | Channel subscriptions | WS lifetime |

---

## 9. Performance Profile

### 9.1 Latency Per Component

| Component | Latency |
|-----------|---------|
| Rust gRPC call | 50–100ms per tick |
| Laravel PHP engines (WorldKernel) | 10–50ms per tick |
| **Total per tick (no narrative)** | **60–150ms** |
| NarrativeLoom chronicle weave | 30s–30min (LLM dependent) |
| Actor intent request | 3–20s (timeout=120s) |
| Centrifugo publish | < 5ms |
| Redis state R/W | < 2ms |
| DB snapshot INSERT | 5–20ms |

### 9.2 Bottleneck Analysis

```
NGHIÊM TRỌNG:
  NarrativeEngine::pulse() gọi synchronous trong SimulationTickPipeline
  → 1 LLM call/tick nếu không throttle → +30s per tick → batch bị phá
  → Fix hiện tại: WORLDOS_NARRATIVE_MIN_TICK_INTERVAL=999999 (skip narrative)
  → Fix đúng: gọi async Celery task (không block tick)

CAO:
  DB snapshot writes: 10,000 ticks = 10,000 large JSON INSERTs
  → Mitigation: Redis cache, chunk mode (chunk=500)

TRUNG BÌNH:
  LLM key exhaustion / rate limiting
  → AiGateway có pool rotation + ReportKeyUsageAction
  → LoomIntentClient fallback to DecisionEngine (rule-based)
  → CircuitBreaker: 3 failures → 60s open window

THẤP:
  State vector serialization: large JSON repeated per tick
  → Solution: Redis cache reduces DB round-trips
```

---

## 10. Vấn Đề Tồn Tại & Khuyến Nghị

### 10.1 Critical

| # | Vấn đề | Vị trí | Impact |
|---|--------|-------|--------|
| **C1** | **Centrifugo channel mismatch** — Loom dùng `narrative:{world_id}:{task_id}`, frontend cũ hardcode `universe.1.narrative` | `centrifugo.py` vs legacy page | Real-time events không đến UI |
| **C2** | **NarrativeEngine::pulse() blocking** — gọi HTTP đồng bộ trong tick loop | `SimulationTickPipeline.php:84` | Batch chạy +30s/tick nếu Loom bật |

### 10.2 High

| # | Vấn đề | Vị trí | Impact |
|---|--------|-------|--------|
| **H1** | **VAF fetch error → black screen** — không có error state nếu API trả lỗi | `narrative-cinema/[id]/page.tsx` | UX: người dùng không biết lỗi gì |
| **H2** | **`/health` permissive** — không có LLM key vẫn báo `healthy` (default) | `routers/system.py` | Monitoring misleading |
| **H3** | **ParticleRenderer canvas cố định** — 960×540 bất kể screen size | `ParticleRenderer.tsx` | Particle sai vị trí trên mobile |

### 10.3 Medium

| # | Vấn đề | Vị trí | Impact |
|---|--------|-------|--------|
| **M1** | **VAFErrorBoundary reset loop** — "Try Again" remount → cùng parse error lặp lại | `CinematicPlayer` | Infinite error loop |
| **M2** | **Schema drift** — TypeScript types vs Pydantic schemas không có CI check | `schemas.py` / `lib/vaf/types.ts` | Silent field drops khi backend thay đổi |
| **M3** | **Thiếu VAF unit tests** | `lib/vaf/` | Không có regression protection |
| **M4** | **news_anchor output flow chưa verify** | `news_anchor.py` → DB | `ResonanceFeed` hiển thị empty |

### 10.4 Low

| # | Vấn đề | Vị trí | Impact |
|---|--------|-------|--------|
| L1 | `NarrationOverlay` animate từng ký tự (500+ motion.span) | Frontend | Frame drops trên mobile |
| L2 | `CameraRenderer` shake không reset đúng sau pause/resume | `CameraRenderer.tsx` | Visual glitch |
| L3 | `PlayerControls` seek cross-scene flicker | `SceneCompositor` | UX flash ngắn |
| L4 | `print()` còn trong agent files | `agents/*.py` | Không structured logging |

---

## 11. Data Flow Tổng Hợp (One-Page View)

### 11.1 Simulation Full Tick

```
[CLI/API] → AdvanceSimulationAction
         → SimulationSupervisor
         → SimulationTickPipeline::run()
              ├─ StateManager::load() ←→ Redis/DB
              ├─ WorldKernel::execute()
              │    ├─ AgentBatchProcessor (actors)
              │    ├─ Phase Engines × 5 (ENVIRONMENT→META)
              │    └─ TickFinalizer
              ├─ ZenithMetricsService
              ├─ StateManager::save() → Redis+DB
              ├─ SnapshotManager → DB
              └─ NarrativeEngine::pulse()
                   └─ NarrativeLoomService::weave()
                        └─ POST /weave-chronicles
                             └─ Celery task
                                  ├─ LangGraph (18 nodes)
                                  ├─ Centrifugo events (per agent)
                                  └─ Laravel webhook callback
```

### 11.2 Frontend Real-time Display

```
[User clicks "Generate Chronicle"]
  → POST /worldos/universes/{id}/generate-chronicle
  ← { task_id, channel: "narrative:{world_id}:{task_id}" }
  → Subscribe Centrifugo channel

[Centrifugo events stream]
  pipeline_started  → isWeaving=true
  agent_started     → node="running"
  agent_done        → node="completed", progress++
  pipeline_done     → show { final_prose, news_headline, storyboard, vfx }

[If WebSocket fails]
  → Poll GET /loom-tasks/{task_id}/status (5s interval)
  → On SUCCESS: same finalizeTaskFromResult()
```

---

## 12. Configuration Reference

### 12.1 Environment Variables Quan Trọng

| Variable | Service | Effect |
|----------|---------|--------|
| `WORLDOS_NARRATIVE_MIN_TICK_INTERVAL` | Laravel | Tần suất Loom calls (999999 = never) |
| `NARRATIVE_LOOM_URL` | Laravel | URL Loom service |
| `WORLDOS_API_URL` | Python Loom | URL Laravel API |
| `CENTRIFUGO_URL` | Python Loom | Centrifugo publisher |
| `CENTRIFUGO_API_KEY` | Python Loom | Auth key |
| `BACKEND_WEBHOOK_URL` | Python Loom | Callback URL sau task |
| `LOOM_HEALTH_STRICT` | Python Loom | `true` = LLM key missing → degraded |
| `LOOM_LLM_TIMEOUT` | Python Loom | Per-provider timeout (default: 20s) |
| `LOCAL_LLM_TIMEOUT` | Python Loom | Local LLM timeout (default: 360s) |
| `SEMANTIC_CACHE_ENABLED` | Python Loom | Redis semantic cache |

### 12.2 Laravel worldos.php Key Settings

| Config key | Default | Notes |
|-----------|---------|-------|
| `worldos.simulation.rust_authoritative` | true | Rust = source of truth cho physics |
| `worldos.simulation.simulation_tick_driver` | `laravel_kernel` | Ai điều phối tick |
| `worldos.autopoiesis.tick_interval` | N | Tần suất axiom mutation |
| `worldos.narrative.min_tick_interval` | 999999 | Chronicles mỗi N tick |
| `worldos.state_cache.driver` | `redis` | State caching backend |
| `worldos.entropy_floor` | 0.001 | Minimum entropy floor |

---

## 13. Action Priority Recommendations

| Priority | Action | Rationale |
|----------|--------|-----------|
| **P0** | Async NarrativeEngine::pulse() — dispatch Celery không block tick | C2 — batch performance critical |
| **P0** | Chuẩn hóa Centrifugo channel: `narrative:{world_id}:{task_id}` everywhere | C1 — real-time monitoring broken |
| **P1** | Error state + retry cho narrative-cinema page | H1 — UX broken on fetch error |
| **P1** | VAF unit tests: parser, timeline, scheduler | M3 — zero test coverage |
| **P2** | CI schema parity check Pydantic ↔ TypeScript | M2 — silent drift risk |
| **P2** | `LOOM_HEALTH_STRICT=true` in production | H2 — monitoring accuracy |
| **P3** | `ResizeObserver` cho ParticleRenderer | H3 — responsive fix |
| **P3** | Prometheus metrics endpoint trên Loom service | Observability |

---

*Tài liệu được tạo bằng phân tích live source code ngày 2026-04-21.*
*Cần cập nhật khi có thay đổi kiến trúc.*
