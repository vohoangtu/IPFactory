# WorldOS V6 Simulation System - Exploration Index

Date: April 8, 2026
Scope: Large-scale simulation (>5000 ticks) investigation
Thoroughness: Medium

## Documentation Generated

### 1. WORLDOS_V6_SIMULATION_GUIDE.md (Main Reference - 500 lines)
Complete reference with all artisan commands, tick architecture, config settings, API endpoints, bottlenecks, and step-by-step guide to run 5000+ tick simulations.

### 2. WORLDOS_QUICK_REFERENCE.md (Cheat Sheet - 350 lines)
Quick start (3 steps), command comparison, performance tuning variables, monitoring, troubleshooting, and full workflow example.

### 3. WORLDOS_ARCHITECTURE_OVERVIEW.md (Visual Guide - 250 lines)
ASCII diagrams, per-tick sequence, config hierarchy, database schema, performance analysis, and command flows.

## Key Findings

### Primary Command for >5000 Ticks

**worldos:simulation-batch**
File: backend/app/Modules/Simulation/Console/Commands/WorldosSimulationBatchCommand.php
Usage: php artisan worldos:simulation-batch {universe_id} --ticks=10000 --chunk=500 --log-every=1000 --output=results.json

Features:
- Unlimited ticks
- Chunking prevents memory overflow
- Logs metrics every N ticks
- JSON/CSV output
- Perfect for calibration runs

### How Ticks Work

Per-tick flow (50-110ms):
1. Load state from Redis/DB
2. Call Rust engine via gRPC (50-100ms)
3. Save snapshot to DB
4. Run post-tick pipeline (fork detection, mutations)
5. Fire events (narrative, etc.)
6. Increment counter

Key Classes:
- SimulationSupervisor (line 50-110) - main loop
- EngineDriver - Rust gRPC caller
- SnapshotManager - DB persistence
- AdvanceSimulationAction - facade

### Critical Configuration (worldos.php)

entropy_floor: 0.001 (minimum entropy)
state_cache.driver: redis (enable for speed!)
simulation_tick_driver: rust_only
tick_pipeline[*].interval: When phases run

### Performance Optimization

For 10,000 ticks:
- Unoptimized: 150-250ms/tick = 25-40 minutes
- Optimized: 50-100ms/tick = 8-16 minutes

Key variables:
WORLDOS_STATE_CACHE_DRIVER=redis              (cache state)
WORLDOS_NARRATIVE_MIN_TICK_INTERVAL=999999    (disable narrative: saves 30s!)
WORLDOS_AUTOPOIESIS_TICK_INTERVAL=1000        (less mutations)
WORLDOS_SNAPSHOT_ARCHIVE_DRIVER=s3            (archive snapshots)

### Performance Bottlenecks (Ranked)

1. Narrative Generation (~30s per pulse if enabled) - WORST
2. Database I/O (10,000 snapshots = 10,000 INSERTs)
3. Rust gRPC Latency (50-100ms per tick, unavoidable)
4. Fork Detection (each tick)
5. State Serialization (JSON blobs)

Solution for #1: WORLDOS_NARRATIVE_MIN_TICK_INTERVAL=999999

### API Endpoints

POST /api/worldos/simulation/advance (Protected)
- Request: {"universe_id": 123, "ticks": 50}
- Max: 1000 ticks per request
- Response: {"ok": true, "snapshot": {...}}

POST /api/worldos/worlds/{id}/pulse (Protected)
- Advances ALL active universes in world
- Request: {"ticks_per_universe": 5}

### Key Files

SimulationSupervisor.php - main tick loop (line 50-110)
EngineDriver.php - Rust gRPC caller
SnapshotManager.php - DB persistence
WorldosSimulationBatchCommand.php - MAIN ENTRY for large runs
UniverseController.php - API endpoint
worldos.php - all config settings

## Quick Start

Step 1: Create universe
php artisan tinker
>>> $world = App\Models\World::first();
>>> $u = App\Models\Universe::create(['world_id' => $world->id, 'status' => 'active']);
>>> exit

Step 2: Run simulation
php artisan worldos:simulation-batch {universe_id} --ticks=10000 --chunk=500 --log-every=1000 --output=results.json

Step 3: Monitor
tail -f results.json | jq '.[-1]'

## Investigation Results

Answers Found:
1. Artisan command: worldos:simulation-batch (best for >5000 ticks)
2. Tick mechanism: SimulationSupervisor with chunking loop
3. Existing test: tests/Feature/WorldosSimulationTest.php
4. Config: backend/config/worldos.php with entropy, scheduler, caching
5. API: POST /api/worldos/simulation/advance (1000 tick limit)

Recommendations:
- Use worldos:simulation-batch --chunk=500 for 5000+ ticks
- Enable Redis: WORLDOS_STATE_CACHE_DRIVER=redis
- Disable narrative: WORLDOS_NARRATIVE_MIN_TICK_INTERVAL=999999
- Expected time: 8-16 minutes for 10,000 ticks (optimized)

