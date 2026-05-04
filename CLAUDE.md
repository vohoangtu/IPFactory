# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WorldOS V6 is a multi-layered world simulation platform. It simulates universes with actors, social graphs, narratives, and evolving epochs.

**Stack:**
- **Backend (Orchestrator):** PHP 8.3 + Laravel 13 — Modular Monolith with DDD
- **Engine (Core):** Rust workspace — gRPC simulation core (port 50051 gRPC, 50052 HTTP)
- **Frontend:** Next.js 16 + React 19 + TypeScript + Tailwind CSS 4
- **NarrativeLoom:** Python + LangGraph — AI narrative generation
- **SocialEngine:** Python + FastAPI — Swarm AI social simulation
- **Database:** PostgreSQL 18 (TimescaleDB) + Neo4j (knowledge/social graphs)
- **Cache/Queue:** Redis Stack + Redpanda (Kafka-compatible)
- **Real-time:** Centrifugo WebSocket
- **Gateway:** Nginx reverse proxy
- **Orchestration:** Docker Compose (all services)

**Language rule:** Respond in Vietnamese (Tiếng Việt) to the user.

## Critical Rules

- **NEVER run `composer`, `npm`, or `cargo` on the host machine.** All commands must run inside Docker containers.
- **Keep PHP at `^8.3`** in `composer.json` — do not upgrade.
- Follow SOLID and KISS principles.
- Use DDD, Event-Driven, Repository, and Action patterns for PHP code.
- Use Vanilla CSS or Tailwind for styling. Avoid standard UI kits unless requested.
- End each session by updating `.dev_status.md` at the project root.

## Development Commands

All commands use the Docker Compose file at `deployment/docker-compose.prod.yml`. Shorthand: `DC` below means `docker compose -f deployment/docker-compose.prod.yml`.

### Start/Stop

```bash
DC up -d --build          # Start all services (first run auto-seeds)
DC down                   # Stop all services
DC down -v                # Stop and destroy volumes
DC logs -f backend        # Tail backend logs
```

### Backend (Laravel)

```bash
DC exec backend php artisan test                          # Run all tests
DC exec backend php artisan test --filter=TestClassName    # Run single test
DC exec backend php artisan test --testsuite=Unit          # Run unit suite only
DC exec backend php artisan migrate                        # Run migrations
DC exec backend php artisan migrate:fresh --seed           # Reset DB with seeds
DC exec backend php artisan worldos:demo-scenario          # Seed demo simulation
DC exec backend php artisan config:clear                   # Clear config cache
DC exec backend bash                                       # Shell into container
```

Tests use PHPUnit with SQLite `:memory:` (configured in `backend/phpunit.xml`). Suites: `Unit`, `Feature`.

Linting: `DC exec backend vendor/bin/pint` (Laravel Pint, PSR-12).

### Frontend (Next.js)

```bash
DC exec frontend npm run dev        # Dev server (port 5000)
DC exec frontend npm run build      # Production build
DC exec frontend npm run check      # TypeScript + ESLint check
DC exec frontend npm run lint       # ESLint only
```

### Engine (Rust)

The Rust workspace (`engine/`) has three crates: `worldos-core`, `worldos-grpc`, `worldos-rules`. Built via `deployment/engine.prod.Dockerfile`.

## Architecture

### Backend — Modular Monolith

Nine independent modules in `backend/app/Modules/`:

| Module | Purpose |
|---|---|
| **Simulation** | Tick cycles, simulation evolution loop |
| **World** | Universe/World/Epoch entity management |
| **SocialGraph** | Actor relationships, social networks |
| **Intelligence** | Traits, personalities, cognition |
| **Psychology** | Emotions, motivations, drives |
| **Narrative** | Story generation, chronicles |
| **Knowledge** | Knowledge graphs, memory systems |
| **Institutions** | Organizations, governance structures |
| **WorldOS** | System-level orchestration, cross-cutting |

Each module follows a consistent structure: `Actions/`, `Services/`, `Entities/`, `Models/`, `Contracts/`, `Events/`, `Jobs/`, `Repositories/`, `Providers/`.

**Action pattern:** Business operations are encapsulated in Action classes (`{Verb}{Noun}Action`) implementing `App\Contracts\ActionInterface`. Each Action has an `execute()` method. Controllers delegate to Actions/Services rather than containing business logic.

**Service Provider structure:** The Simulation module splits its provider into sub-providers: `RepositoryServiceProvider`, `EngineServiceProvider`, `KernelServiceProvider`, `PipelineServiceProvider` — all coordinated by the main `SimulationServiceProvider`.

**Cross-module contracts:** Modules communicate through interfaces in `app/Contracts/` (e.g., `RuleVmInterface`, `DecisionEngineInterface`). Never import concrete classes from another module directly.

**API Authentication:** Mutation routes (POST/PATCH/DELETE) are protected with `auth:sanctum`. GET routes are public.

### Simulation Loop (5 phases)

1. **Global Update** — Macro forces affect the world
2. **Local Update** — Per-agent decisions and actions
3. **Cascade/Diffusion** — Propagate changes through networks
4. **Post-Process** — Record history, take snapshots
5. **Broadcast** — Push real-time updates via Centrifugo

### Core Engines

- **Entropy Engine** — Disorder, decay, randomness
- **Observation Engine** — Probability collapse (quantum-inspired)
- **Epoch Engine** — Historical era transitions (Bronze → Iron → Modern)
- **Cascade Diffusion** — Social/ecological spread effects
- **Prophecy Engine** — Scenario forecasting

### Domain Model

- **Multiverse** → contains parallel **Universes** → each has **Worlds** → divided into **Epochs**
- **Actor** — Agent with traits, personality, social connections
- **Relic** — Persistent state object across epochs

### Event-Driven Architecture

- Domain Events trigger cross-module communication
- Centrifugo for real-time WebSocket broadcasting to frontend
- Redis Streams or Redpanda (Kafka) as event bus
- Laravel Queue (Redis-backed) for async jobs
- Separate `scheduler` and `worker` containers handle background processing

### Frontend

Next.js App Router (`frontend/src/app/`). Key libraries: React Three Fiber (3D), ReactFlow, Recharts, TanStack React Query (data fetching), Centrifuge client (real-time), Framer Motion.

**Data fetching:** All hooks use TanStack React Query (`useQuery`/`useMutation`). Hooks are in `frontend/src/hooks/`.

**Shared utilities:** `frontend/src/lib/centrifugo.ts` (WebSocket factory), `frontend/src/lib/api.ts` (Axios client), `frontend/src/lib/log-utils.ts` (log parsing).

**Dashboard decomposition:** Tab components live in `frontend/src/components/dashboard/tabs/`. Layout uses a `DashboardShell` client component wrapper to preserve SSR in the layout itself.

### Python Services

- **narrative-loom/** — LangGraph-based narrative pipeline. Connects to multiple LLM providers (OpenAI, Anthropic, Google, Groq, local).
- **sim/social-engine/** — FastAPI swarm AI for social dynamics simulation.

## Service Ports (Docker internal)

| Service | Port |
|---|---|
| Nginx (gateway) | 80 |
| Backend (PHP-FPM) | 9000 (internal only) |
| Frontend (Next.js) | 5000 |
| Engine gRPC | 50051 |
| Engine HTTP | 50052 |
| PostgreSQL | 5432 |
| Redis | 6379 |
| Neo4j Browser | 7474 |
| Centrifugo | 8000 (internal) |
| Redpanda Kafka | 9092 |

### Rust Engine — Trait Constants

Trait indices are defined as named constants in `engine/worldos-core/src/agent.rs` (`TRAIT_DOMINANCE`, `TRAIT_AMBITION`, etc.). Both `worldos-core` and `worldos-grpc` must use these shared constants — never hardcode trait indices.

## Key Config Files

- `backend/config/worldos.php` (49KB) — Central simulation configuration; defines all engine parameters, tick settings, actor archetypes
- `backend/config/ai.php` — LLM provider integrations (OpenAI, OpenRouter, Anthropic)
- `deployment/docker-compose.prod.yml` — Full stack service definitions
- `backend/phpunit.xml` — Test configuration

## Naming Conventions

- Actions: `{Verb}{Noun}Action` (e.g., `RunSimulationAction`)
- Services: `{Noun}Service`
- Repositories: `{Noun}Repository`
- React Components: PascalCase
- DB tables: snake_case
- PHP: PSR-12, strict typing (`declare(strict_types=1)`)
- TypeScript: strict mode, functional components

## Documentation

- `docs/WORLDOS_V6.md` (53KB) — Main architecture document
- `docs/ULTIMATE_ARCHITECTURE.md` — High-level design
- `docs/WORLDOS_ACTOR_ARCHETYPE_SYSTEM.md` (336KB) — Actor system specification
- `AI_CONTEXT.md` — Vietnamese-language AI agent context
- `.cursorrules` — Cursor IDE rules
- `.dev_status.md` — Current development session state
- `DOCKER_GUIDE.md` — Docker setup guide
- Each module has its own `README.md` in `backend/app/Modules/{Module}/`
