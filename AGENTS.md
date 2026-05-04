# WorldOS V6 - Agent Guide

> **Agent-facing project reference.** This document assumes zero prior knowledge of WorldOS.
> When speaking to the user, always respond in **Vietnamese (Tieng Viet)**.

---

## 1. Project Overview

**WorldOS V6** is a multi-layered world-evolution simulation platform. It models universes, parallel branches (multiverse), actors with psychology and social graphs, civilizations, ecological systems, and narrative chronicles that evolve across epochs.

The system is split into an **Orchestrator** (PHP backend), a **Core Engine** (Rust simulation kernel), **AI narrative services** (Python/LangGraph), a **social swarm simulator** (Python/FastAPI), and a **Next.js frontend** for visualization and control.

---

## 2. Critical Rules

1. **Docker-first.** NEVER run `composer`, `npm`, `cargo`, `pip install`, or `pytest` on the host machine. All commands must run inside containers.
2. **Keep PHP at `^8.3`** in `backend/composer.json`. Do not upgrade it even though the production Dockerfile currently uses `php:8.4-fpm`.
3. **End every session** by updating `.dev_status.md` at the project root with the current state.
4. **Communication language:** Always respond to the user in Vietnamese.
5. **Cross-module coupling:** PHP modules communicate through interfaces in `backend/app/Contracts/`. Never import concrete classes from another module directly.

---

## 3. Technology Stack

| Layer | Technology | Purpose |
|---|---|---|
| **Backend (Orchestrator)** | PHP 8.3 + Laravel 13 | Modular monolith API, queue workers, scheduler |
| **Engine (Core)** | Rust (Edition 2021) | Deterministic simulation tick, gRPC/HTTP server |
| **Frontend** | Next.js 16 + React 19 + TypeScript 5 | Dashboard, 3D visualization, real-time UI |
| **Styling** | Tailwind CSS v4 | CSS-based configuration (no `tailwind.config.js`) |
| **Narrative Loom** | Python 3.11 + FastAPI + LangGraph | AI narrative/chronicle generation pipeline |
| **Social Engine** | Python 3.11 + FastAPI + CAMEL/OASIS | Swarm AI social simulation (Twitter/Reddit) |
| **Primary Database** | PostgreSQL 18 + TimescaleDB | Relational data + time-series snapshots |
| **Graph Database** | Neo4j 2026.02 (optional) | Social/knowledge/causality graphs |
| **Cache / Queue** | Redis Stack 7.4 | Cache, sessions, queues, pub/sub, vector store |
| **Event Stream** | Redis Streams (default) or Redpanda (Kafka API) | Cross-service event bus |
| **Real-time** | Centrifugo v6 | WebSocket push to frontend |
| **Gateway** | Nginx 1.27-alpine | Reverse proxy + static file serve |
| **Orchestration** | Docker Compose | Single-file stack: `deployment/docker-compose.prod.yml` |

---

## 4. Architecture

### 4.1 Backend - Modular Monolith

Located in `backend/`. Laravel 13 application with nine domain modules under `app/Modules/`:

| Module | Responsibility |
|---|---|
| **Simulation** | Tick cycles, engine registry, phase pipeline, state management |
| **World** | Universe, World, Epoch, Zone entity management |
| **SocialGraph** | Actor relationships, social networks, graph queries |
| **Intelligence** | AI drivers, actor behavior, archetypes, LLM gateway |
| **Psychology** | GOAP planner, Jungian behavior, identity, meaning engine |
| **Narrative** | Chronicle generation, mythology, religion, serial stories |
| **Knowledge** | Wiki engine, knowledge graphs, memory systems |
| **Institutions** | Supreme entities, social contracts, governance |
| **WorldOS** | System-level providers, cross-cutting concerns |

Each module contains: `Actions/`, `Services/`, `Entities/`, `Models/`, `Contracts/`, `Events/`, `Jobs/`, `Repositories/`, `Providers/`, `Http/Controllers/`, `routes/api.php`.

Key patterns:
- **Action pattern:** Business logic lives in `{Verb}{Noun}Action` classes implementing `App\Contracts\ActionInterface`. This is a marker interface; each Action declares its own strongly-typed `execute()` signature. Controllers delegate to Actions, never contain business logic.
- **Repository pattern:** Interfaces in `app/Contracts/Repositories/` bound to Eloquent implementations.
- **Event-driven:** Domain events (e.g., `UniverseSimulationPulsed`) trigger ordered listeners across modules, then broadcast via Centrifugo and/or Kafka.
- **Simulation loop (5 phases):** Global Update -> Local Update -> Cascade/Diffusion -> Post-Process -> Broadcast.

### 4.2 Engine - Rust Workspace

Located in `engine/`. Cargo workspace with three crates:

| Crate | Role |
|---|---|
| `worldos-core` | Core types, simulation systems, state model, DSL AST |
| `worldos-grpc` | gRPC + HTTP server (Tonic + Axum), Kafka producer |
| `worldos-rules` | DSL parser, rule evaluation VM, Wasmtime integration |

The gRPC service (`SimulationEngine`) exposes methods: `Advance`, `Merge`, `Observe`, `BatchAdvance`, `AnalyzeTrajectory`, `EvaluateRules`, `ProcessActorsSoA`, `ProcessFieldsV7`, `ComputeMetabolismGrid`, `CalculateVocationAlignment`, `GetCombinedGravity`.

Serialization: JSON by default; `bincode` used in gRPC when input does not start with `{`.

### 4.3 Narrative Loom

Located in `narrative-loom/`. FastAPI app + Celery worker. LangGraph pipeline with ~16 AI agent nodes and deterministic engine nodes (entropy, attractor, arc, phase engines).

- Pipeline state: `NarrativeState` (TypedDict, `total=False`) flowing through the graph.
- Nodes are wrapped with `@agent_node("name")` decorator for structured logging, retry, Centrifugo progress updates, and metrics.
- Dynamic LLM routing via cascading fallback: runtime override -> backend AI Key Pool -> backend DB -> local `configs/agent_routing.json` -> local LLM.
- Tick-based caching (`TickBasedCache`) keys outputs by `(world_id, current_tick, provider, prompt)`.

### 4.4 Social Engine

Located in `sim/social-engine/`. FastAPI service using CAMEL/OASIS framework to simulate Twitter and Reddit in parallel. Agents perform actions (`CREATE_POST`, `LIKE_POST`, `FOLLOW`, `INTERVIEW`, etc.).

- IPC via JSON drop-files in `ipc_commands/` and `ipc_responses/`.
- Uses Zep Cloud for graph memory.

### 4.5 Frontend

Located in `frontend/`. Next.js 16 App Router (`src/app/`). Dark-only theme (`#080810` base, cyan/violet accents).

- Data fetching: TanStack React Query v5 with feature-based colocation (`features/<domain>/api/queries.ts`).
- Real-time: Centrifuge WebSocket client.
- 3D visualization: React Three Fiber + Drei.
- Custom animation pipeline: **VAF** (Visual Animation Format) under `src/lib/vaf/` and `src/components/vaf/`.

---

## 5. Directory Structure

```
IPFactory/
├── backend/              # Laravel 13 modular monolith
│   ├── app/
│   │   ├── Modules/      # 9 domain modules
│   │   ├── Contracts/    # Global interfaces
│   │   ├── Models/       # 60+ Eloquent models
│   │   ├── Protogen/     # Generated protobuf PHP classes
│   │   └── Actions/      # Domain actions
│   ├── config/           # Laravel configs + worldos*.php domain configs
│   ├── database/
│   │   ├── migrations/   # 170+ migrations (TimescaleDB hypertables)
│   │   └── seeders/      # 20+ seeders
│   ├── routes/           # api.php stub; real routes live in modules
│   ├── tests/            # PHPUnit: Unit/ + Feature/
│   └── composer.json
├── engine/               # Rust workspace
│   ├── worldos-core/     # Simulation types & systems
│   ├── worldos-grpc/     # gRPC/HTTP server
│   ├── worldos-rules/    # DSL rule engine
│   └── proto/            # Protobuf definitions
├── frontend/             # Next.js 16
│   ├── src/
│   │   ├── app/          # App Router pages
│   │   ├── components/   # UI primitives, dashboard, VAF renderers
│   │   ├── features/     # Feature-based API colocation
│   │   ├── hooks/        # Shared React hooks
│   │   └── lib/          # Axios, Centrifugo, VAF pipeline, utils
│   └── package.json
├── narrative-loom/       # Python FastAPI + LangGraph + Celery
│   ├── agents/           # 16 LLM agent nodes
│   ├── engines/          # Deterministic pipeline nodes
│   ├── core/             # Infrastructure (logging, metrics, retry)
│   ├── routers/          # FastAPI routes
│   ├── tasks/            # Celery background tasks
│   └── tests/            # pytest suite
├── sim/                  # Python social simulation
│   └── social-engine/    # FastAPI + CAMEL/OASIS
├── deployment/           # Docker, Nginx, compose
│   ├── docker-compose.prod.yml
│   ├── *.prod.Dockerfile
│   └── nginx/
└── openspec/             # Spec-driven change tracking
    ├── changes/
    └── specs/
```

---

## 6. Build & Development Commands

All commands use the Docker Compose file at `deployment/docker-compose.prod.yml`.

Shorthand: `DC = docker compose -f deployment/docker-compose.prod.yml`

### 6.1 Start / Stop

```bash
DC up -d --build          # Start all services (first run auto-seeds)
DC down                   # Stop all services
DC down -v                # Stop and destroy volumes
DC logs -f backend        # Tail backend logs
DC exec backend bash      # Shell into backend container
```

### 6.2 Backend (Laravel)

```bash
DC exec backend php artisan test                          # Run all tests
DC exec backend php artisan test --filter=TestClassName    # Run single test
DC exec backend php artisan test --testsuite=Unit          # Run unit suite only
DC exec backend php artisan migrate                        # Run migrations
DC exec backend php artisan migrate:fresh --seed           # Reset DB with seeds
DC exec backend php artisan worldos:demo-scenario          # Seed demo simulation
DC exec backend php artisan config:clear                   # Clear config cache
DC exec backend vendor/bin/pint                           # Lint with PSR-12
```

Composer scripts (inside container or local-only dev):
```bash
composer setup            # Full install + migrate + npm build
composer dev              # Concurrent dev servers (Laravel, queue, Pail, Vite)
composer test             # config:clear + php artisan test
```

### 6.3 Frontend (Next.js)

```bash
DC exec frontend npm run dev        # Dev server (port 5000)
DC exec frontend npm run build      # Production build (standalone output)
DC exec frontend npm run check      # TypeScript + ESLint
DC exec frontend npm run lint       # ESLint only
DC exec frontend npm test           # Vitest run
DC exec frontend npm run test:watch # Vitest watch mode
```

### 6.4 Engine (Rust)

```bash
cd engine
cargo build --release -p worldos-grpc --bin worldos-engine   # Build server
cargo test                                                     # Run tests
cargo run --bin worldos-engine                                 # Run locally
```

Default ports: gRPC `50051`, HTTP `50052`. Override with `GRPC_ADDR` and `HTTP_ADDR`.

### 6.5 Narrative Loom (Python)

```bash
# API mode
uvicorn main:app --host 0.0.0.0 --port 8001 --loop uvloop

# Worker mode
MODE=worker celery -A core.celery_app worker -Q narrative -c 2 --loglevel=info
```

### 6.6 Social Engine (Python)

```bash
python sim/social-engine/main.py    # Uvicorn on 0.0.0.0:5001
```

---

## 7. Testing

| Component | Runner | Config / Pattern |
|---|---|---|
| **Backend** | PHPUnit 12.5+ | `backend/phpunit.xml` - suites `Unit` and `Feature`; default env uses SQLite `:memory:` |
| **Frontend** | Vitest v2 | `vitest.config.ts` - `node` env, globals enabled; patterns `src/**/*.test.ts(x)` |
| **Engine** | Built-in `cargo test` | Tests in `worldos-core` and `worldos-rules` crates covering determinism, invariants, diffusion |
| **Narrative Loom** | pytest + pytest-asyncio | `tests/` directory; heavy use of `mocker.patch` on `get_llm` / `get_llm_for_agent` |
| **Social Engine** | pytest (declared) | No actual test files exist in `sim/` at time of writing |

Backend test environment defaults:
- `DB_CONNECTION=sqlite` (`:memory:`)
- `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array`
- `BROADCAST_CONNECTION=null`, `MAIL_MAILER=array`

---

## 8. Code Style & Conventions

### 8.1 PHP
- **Standard:** PSR-12 via Laravel Pint.
- **Strict typing:** Every file starts with `declare(strict_types=1);`.
- **Patterns:** DDD, Event-Driven, Repository, Action.
- **Naming:**
  - Actions: `{Verb}{Noun}Action` (e.g., `SpawnActorAction`)
  - Services: `{Noun}Service`
  - Repositories: `{Noun}Repository`
  - Events: `{Noun}{Verb}ed` (past tense)
  - DB tables: `snake_case`

### 8.2 TypeScript / React
- **Standard:** Strict mode (`strict: true`), functional components.
- **Path alias:** `@/*` maps to `./src/*`.
- **Components:** PascalCase.
- **Class merging:** Use `cn()` from `clsx` + `tailwind-merge`.

### 8.3 Rust
- Trait indices are named constants in `engine/worldos-core/src/agent.rs` (`TRAIT_DOMINANCE`, `TRAIT_AMBITION`, etc.). Never hardcode trait indices across crates.
- All core types derive `Serialize`/`Deserialize`.

### 8.4 Python
- Structured logging via `structlog` (JSON in production, colored console in dev).
- All modules use `from core.logging import get_logger`.
- Error taxonomy: `TransientLLMError` (retryable), `PermanentLLMError` (fail-fast), `PipelineError`.

### 8.5 Database
- Migrations are very granular and date-ordered (170+ files).
- TimescaleDB hypertables are used for time-series snapshot data.
- Eloquent models use explicit `$fillable`, `$casts`, and typed relationships.

---

## 9. Deployment

### 9.1 Docker Compose Services

| Service | Image / Build | Exposed Ports | Notes |
|---|---|---|---|
| `nginx` | `nginx:1.27-alpine` | Host `80` (or `8080` per DOCKER_GUIDE.md) | Reverse proxy; only entrypoint from host |
| `backend` | `backend.prod.Dockerfile` | Internal `9000` | PHP-FPM; `vendor` baked into image |
| `scheduler` | Same as backend | Internal | Laravel cron/scheduler |
| `worker` | Same as backend | Internal | Laravel queue worker |
| `frontend` | `frontend.prod.Dockerfile` | Internal `3000` | Next.js standalone |
| `engine` | `engine.prod.Dockerfile` | Internal `50051`, `50052` | Rust gRPC + HTTP |
| `postgres` | `timescale/timescaledb-ha:pg18` | Internal `5432` | Primary DB |
| `redis` | `redis/redis-stack-server:7.4.0-v3` | Internal `6379` | Auth-enabled |
| `neo4j` | `neo4j:2026.02.3-community` | Internal `7474`, `7687` | Optional (`WORLDOS_GRAPH_ENABLED`) |
| `redpanda` | `redpandadata/redpanda:v25.3.10` | Internal `9092` | Optional Kafka API |
| `narrative_loom` | `narrative-loom/Dockerfile` | Internal `8001` | FastAPI |
| `narrative_loom_worker` | Same | Internal | Celery worker |
| `social_engine` | `sim/social-engine/Dockerfile` | Internal `5001` | FastAPI |
| `centrifugo` | `centrifugo/centrifugo:v6` | Host `8000` | WebSocket real-time |

### 9.2 Nginx Routing

- `/api/sim/*` -> Rust Engine HTTP (`50052`)
- `/api/*` -> Laravel Backend PHP-FPM (`9000`)
- `/connection/websocket` -> Centrifugo (`8000`)
- `/storage` -> Backend static files
- `/*` -> Next.js Frontend (`3000`)

### 9.3 Health Checks

Postgres, Redis, Neo4j, and Redpanda all define Docker health checks. Backend services use `depends_on` with `condition: service_healthy` where applicable.

---

## 10. Security Considerations

- **Authentication:** Laravel Sanctum for API token auth. Mutation routes (POST/PATCH/DELETE) require `auth:sanctum`. GET routes are public by default.
- **JWT:** Firebase PHP-JWT used for token handling.
- **Real-time:** Centrifugo uses HMAC token auth; secrets managed via environment variables.
- **CORS:** Hardened middleware in backend; Nginx adds security headers (`X-Frame-Options: DENY`, etc.).
- **Rate limiting:** Nginx `limit_req_zone` at 30 r/s with burst 20 for API routes.
- **Containers:** Non-root users in frontend (`nextjs`), engine (`engine`), and other service images.
- **Secrets:** Redis auth enabled. Neo4j password set via environment.
- **Graph DB:** Neo4j integration is optional and disabled by default (`WORLDOS_GRAPH_ENABLED=false`). A `NullWorldOsGraphService` acts as fallback.

---

## 11. Communication & Workflow

- **Language:** When interacting with the user, always use **Vietnamese (Tieng Viet)**.
- **Session tracking:** Update `.dev_status.md` at the project root after each working session.
- **OpenSpec workflow:** The project uses an experimental spec-driven change workflow under `openspec/`. Active specs live in `openspec/specs/`; archived changes in `openspec/changes/archive/`.
- **Documentation:** Key architecture docs are in `docs/` (`WORLDOS_V6.md`, `ULTIMATE_ARCHITECTURE.md`, `WORLDOS_ACTOR_ARCHETYPE_SYSTEM.md`) and module-level `README.md` files inside `backend/app/Modules/{Module}/`.

---

## 12. Key Configuration Files

| File | Purpose |
|---|---|
| `backend/composer.json` | PHP dependencies (keep `php: ^8.3`) |
| `backend/config/worldos.php` | Central simulation config (engines, ticks, archetypes) |
| `backend/config/ai.php` | LLM provider integrations |
| `backend/phpunit.xml` | PHPUnit test configuration |
| `frontend/package.json` | Node.js dependencies |
| `frontend/vitest.config.ts` | Vitest configuration |
| `engine/Cargo.toml` | Rust workspace manifest |
| `narrative-loom/requirements.txt` | Python dependencies |
| `deployment/docker-compose.prod.yml` | Full-stack orchestration |
| `.cursorrules` | IDE rules (Cursor) |
| `CLAUDE.md` | Extended Claude Code guidance |
| `AI_CONTEXT.md` | Vietnamese AI agent context |
