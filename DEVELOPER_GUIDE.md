# WorldOS V6 — Developer Guide

Quick-start guide for new developers joining the WorldOS V6 project.

## Architecture Overview (30 seconds)

WorldOS V6 is a **multi-layered world simulation platform** with 5 services:

| Service | Language | Role | Port |
|---|---|---|---|
| **Backend** | PHP 8.3 + Laravel 13 | Orchestrator — simulation tick loop, 9 business modules | 9000 (internal) |
| **Engine** | Rust (tonic/gRPC) | Simulation core — agent behavior, universe physics, rules | 50051 (gRPC), 50052 (HTTP) |
| **Frontend** | Next.js 16 + React 19 | Dashboard — 3D visualization, real-time logs, social graphs | 5000 |
| **NarrativeLoom** | Python + LangGraph | AI narrative generation — 16 specialized agents | 8001 |
| **SocialEngine** | Python + FastAPI | Swarm AI — social dynamics simulation | 8002 |

Supporting infrastructure: PostgreSQL 18 (+TimescaleDB), Neo4j, Redis Stack, Redpanda (Kafka), Centrifugo (WebSocket), Nginx (gateway).

## Prerequisites

- **Docker** & **Docker Compose** v2+
- **Node.js** ≥ 22 (host only for tooling; frontend runs in Docker)
- **pnpm** ≥ 10 (for manage-anything plugin)
- **Rust** (optional, for local engine development)
- **Python** ≥ 3.11 (optional, for local Python service development)

## Quick Start

```bash
# Clone
git clone <repo-url> worldos-v6
cd worldos-v6

# Start all services (first run auto-seeds demo data)
docker compose -f deployment/docker-compose.prod.yml up -d --build

# Check health
docker compose -f deployment/docker-compose.prod.yml ps

# View logs
docker compose -f deployment/docker-compose.prod.yml logs -f backend
```

After startup:
- **Frontend**: http://localhost:5000
- **Engine gRPC**: localhost:50051
- **Engine HTTP**: localhost:50052
- **Neo4j Browser**: http://localhost:7474

## Development Commands

Use `DC` as shorthand: `DC="docker compose -f deployment/docker-compose.prod.yml"`

### Backend (Laravel)

```bash
# Run all tests
DC exec backend php artisan test

# Run single test suite
DC exec backend php artisan test --testsuite=Unit
DC exec backend php artisan test --testsuite=Feature

# Run single test
DC exec backend php artisan test --filter=TestClassName

# Database
DC exec backend php artisan migrate:fresh --seed
DC exec backend php artisan worldos:demo-scenario

# Lint
DC exec backend vendor/bin/pint

# Shell
DC exec backend bash
```

### Frontend (Next.js)

```bash
# Dev server
DC exec frontend npm run dev

# TypeScript + ESLint check
DC exec frontend npm run check

# Tests
DC exec frontend npm test
DC exec frontend npm run test:coverage
```

### Rust Engine

```bash
# Inside engine/ directory:
cargo test                    # All tests
cargo test -p worldos-core    # Core crate only
cargo test -p worldos-rules   # Rules DSL crate
cargo build --release         # Production build
```

### Python Services

```bash
# NarrativeLoom tests
cd narrative-loom
pytest tests/ -v

# SocialEngine tests
cd sim/social-engine
pytest tests/ -v
```

## Project Structure

```
worldos-v6/
├── backend/                  # Laravel modular monolith
│   ├── app/
│   │   ├── Modules/          # 9 business modules (Simulation, World, etc.)
│   │   │   └── {Module}/
│   │   │       ├── Actions/      # Business operations (Action pattern)
│   │   │       ├── Services/     # Domain services
│   │   │       ├── Entities/     # Domain entities (DDD)
│   │   │       ├── Models/       # Eloquent models
│   │   │       ├── Contracts/    # Interfaces for cross-module communication
│   │   │       ├── Events/       # Domain events
│   │   │       ├── Jobs/         # Async queue jobs
│   │   │       ├── Repositories/ # Data access
│   │   │       └── Providers/    # Service providers
│   │   ├── Contracts/        # Global cross-cutting contracts
│   │   ├── Broadcasting/     # Centrifugo WebSocket broadcaster
│   │   └── Services/         # Core services (AiGateway, CircuitBreaker)
│   ├── config/
│   │   ├── worldos.php       # Central simulation configuration (49KB)
│   │   └── ai.php            # LLM provider integrations
│   ├── database/migrations/
│   ├── routes/
│   └── tests/
│       ├── Unit/
│       └── Feature/
├── engine/                   # Rust workspace
│   ├── worldos-core/         # Domain model + simulation logic
│   ├── worldos-grpc/         # gRPC/HTTP transport + service adapter
│   └── worldos-rules/        # Rules DSL parser + evaluator
├── frontend/                 # Next.js App Router
│   ├── src/
│   │   ├── app/              # App Router pages
│   │   ├── components/
│   │   │   └── dashboard/    # Dashboard shell + tabs
│   │   ├── features/         # Feature-sliced hooks + API queries
│   │   ├── hooks/            # Shared React hooks
│   │   ├── lib/              # API client, Centrifugo, log utils
│   │   └── types/            # Shared TypeScript types
├── narrative-loom/           # LangGraph AI narrative pipeline
│   ├── agents/               # 16 specialized AI agents
│   ├── engines/              # Narrative analysis engines
│   ├── core/                 # Infrastructure (Celery, Centrifugo, metrics)
│   ├── routers/              # FastAPI route handlers
│   └── knowledge/            # Era definitions, power manifestos
├── sim/social-engine/        # Swarm AI social simulation
│   ├── app/
│   │   ├── api/              # FastAPI routes
│   │   ├── services/         # Simulation manager, runner, graph builder
│   │   ├── models/           # Pydantic data models
│   │   └── utils/            # LLM client, logger, cache
│   └── tests/
└── deployment/               # Docker Compose + Dockerfiles
```

## Key Patterns

### Backend: Action Pattern
Business operations are Action classes implementing `ActionInterface`:
```php
class RunSimulationAction implements ActionInterface {
    public function execute(Universe $universe, int $tick): EngineResult { ... }
}
```

### Backend: Cross-Module Communication
- Modules communicate via interfaces in `app/Contracts/`
- Domain events trigger cross-module handlers
- Never import concrete classes from another module directly

### Frontend: Data Fetching
- TanStack React Query hooks in `features/{domain}/hooks/`
- API queries in `features/{domain}/api/queries.ts`
- Axios client in `lib/api.ts` with automatic JWT token injection

### Rust Engine: Simulation Loop (5 phases)
1. **Global Update** — Macro forces affect the world
2. **Local Update** — Per-agent decisions and actions
3. **Cascade/Diffusion** — Propagate changes through networks
4. **Post-Process** — Record history, take snapshots
5. **Broadcast** — Push updates via Centrifugo

### Python: LangGraph Pipeline
- `graph.py` defines the narrative generation state machine
- Each agent is a node that transforms NarrativeState
- `agent_wrapper.py` wraps LLM calls with logging/monitoring

## Testing

- **Backend**: PHPUnit with SQLite `:memory:` (Unit + Feature suites)
- **Frontend**: Vitest + jsdom + React Testing Library
- **Rust**: `#[cfg(test)]` inline tests + integration tests
- **Python**: pytest + pytest-asyncio with mocked LLM calls

Target: every production code path should have at least one test.

## Docker Architecture

14 containers managed by `deployment/docker-compose.prod.yml`:
- `nginx` — reverse proxy (port 80)
- `backend` — PHP-FPM Laravel
- `scheduler` — Laravel scheduler (cron)
- `worker` — Laravel queue worker
- `engine` — Rust gRPC simulation core
- `frontend` — Next.js standalone
- `postgres` — PostgreSQL 18 + TimescaleDB
- `neo4j` — Knowledge/social graph database
- `redis` — Cache + queue backend
- `redpanda` — Kafka-compatible event streaming
- `centrifugo` — WebSocket real-time server
- `narrative_loom` — Python AI narrative service
- `narrative_loom_worker` — Celery worker for async narrative tasks
- `social_engine` — Python swarm AI service

## Architecture Decision Records (ADRs)

### ADR-001: Modular Monolith vs Microservices
**Decision**: Modular Monolith with DDD.  
**Rationale**: 9 modules share the same database and transaction boundary. Extracting to microservices would add network latency to the simulation tick loop (which requires fast sequential engine execution). Modules communicate via contracts, so extraction is possible later if needed.

### ADR-002: Rust for Simulation Core vs PHP
**Decision**: Rust (tonic/gRPC) for the performance-critical simulation core, PHP (Laravel) for orchestration.  
**Rationale**: Agent processing, cascade diffusion, and rule evaluation are CPU-bound and benefit from Rust's zero-cost abstractions. Laravel handles HTTP routing, auth, queue management, and database access where developer velocity matters more than raw performance.

### ADR-003: LangGraph for AI Narrative Pipeline
**Decision**: Python LangGraph for narrative generation with 16 specialized agents.  
**Rationale**: LangGraph's state-machine model naturally maps to the multi-phase narrative generation pipeline (outline → psychology → storyboard → prose → critique). Each agent is a LangGraph node with typed state transitions.

### ADR-004: Centrifugo for Real-Time Events
**Decision**: Centrifugo WebSocket for real-time simulation event broadcasting.  
**Rationale**: Centrifugo provides channel-based pub/sub with JWT auth, binary protocol support, and built-in presence/history. It decouples the backend from frontend WebSocket management.

### ADR-005: Protobuf/gRPC for Service Contracts
**Decision**: Protobuf (proto3) for all inter-service contracts.  
**Rationale**: Strongly typed, language-agnostic schema shared by Rust engine, Laravel backend, and Python services. Protobuf code generation eliminates manual serialization bugs.

## Common Tasks

### Adding a new simulation engine (Backend)
1. Create engine class in `app/Modules/Simulation/Core/Engines/{Category}/`
2. Implement `SimulationEngine` interface (phase, name, priority, tickRate, handle)
3. Register in `SimulationServiceProvider` or `KernelServiceProvider`
4. Write unit test in `tests/Unit/Simulation/`

### Adding a new AI agent (NarrativeLoom)
1. Create agent file in `narrative-loom/agents/`
2. Decorate with `@agent_node` from `core/agent_wrapper.py`
3. Add to the LangGraph pipeline in `graph_builder.py`
4. Write test in `narrative-loom/tests/test_agents.py`

### Adding a new Dashboard tab (Frontend)
1. Create tab component in `frontend/src/components/dashboard/tabs/`
2. Add hook in `frontend/src/features/{domain}/hooks/`
3. Register tab in `DashboardShell.tsx` tab list
4. Run `npm run check` to verify types

## Knowledge Graph

This project includes Understand-Anything knowledge graphs for architecture exploration. Launch via:
```bash
/understand-dashboard
```

Graphs exist for: `engine/`, `sim/`, `narrative-loom/`, `backend/`, `frontend/`.
