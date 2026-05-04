# WorldOS V6 Simulation System - Large-Scale Simulation (>5000 ticks) Exploration

## Executive Summary

The WorldOS V6 simulation system is a Laravel-based orchestration layer for a gRPC-based Rust simulation engine. It supports running large-scale simulations through batch commands, APIs, and continuous autonomous modes.

## 1. ARTISAN COMMANDS FOR SIMULATION CONTROL

### Primary Commands:

1. **worldos:simulation-batch** (BEST for >5000 ticks)
   - File: backend/app/Modules/Simulation/Console/Commands/WorldosSimulationBatchCommand.php
   - Usage: php artisan worldos:simulation-batch {universe_id} --ticks=10000 --chunk=100 --log-every=100
   - Features: Loops with chunking, logs metrics to JSON/CSV, prevents memory overflow

2. **worldos:demo-scenario**
   - File: backend/app/Modules/Simulation/Console/Commands/RunDemoScenario.php
   - Creates default world, runs 10 stable ticks, injects crisis, detects forks

3. **worldos:sim**
   - File: backend/app/Modules/Simulation/Console/Commands/WorldosSimCommand.php
   - Usage: php artisan worldos:sim [universe_id] --ticks=5
   - Shows resulting chronicle and metrics

4. **simulation:advance-v3**
   - File: backend/app/Modules/Simulation/Console/Commands/AdvanceSimulationCommand.php
   - Advances ALL universes in a world by N ticks
   - Uses ImplicitOrchestratorService::runBatch()

5. **worldos:run-continuous**
   - File: backend/app/Modules/Simulation/Console/Commands/WorldOSRunContinuousCommand.php
   - Infinite loop daemon mode for autonomic simulations
   - Usage: php artisan worldos:run-continuous --ticks=1 --sleep=2

6. **worldos:benchmark-tick**
   - Measures tick_duration_ms for performance baseline

## 2. HOW TICKS WORK

### Tick Advancement Flow:

```
AdvanceSimulationAction::execute($universeId, $ticks)
  → SimulationSupervisor::execute($universeId, $ticks)
    → Loop for each tick (i=0 to ticks-1):
      1. EngineDriver::advance($universe, 1)
         - prepareEngineStateInput() [get state from DB/cache]
         - prepareWorldConfig() [get world config]
         - Rust gRPC call: engine.advance()
         - ensureEntropyFloor() [clamp to >= 0.001]
      2. StateSynchronizer::sync()
      3. SnapshotManager::persistOrVirtual()
         - Save to universe_snapshots table
         - Add engine_health, last_tick_ms to metrics
      4. RuntimePipeline::run()
         - Decision engine, fork detection, axiom mutations
      5. EventDispatcher::dispatchPulsed()
         - Fire UniverseSimulationPulsed event
```

### State Vector Structure:
```php
[
  'entropy' => 0.0–1.0,
  'zones' => [{id, state: {base_mass, structured_mass, pressure, ...}, neighbors}],
  'institutions' => [...],
  'macro_agents' => [...],
  'attractors' => [...],
  'scars' => [...]
]
```

### Tick Pipeline (config/worldos.php):
```php
'tick_pipeline' => [
  'actor' => ['interval' => 1],          // Every tick
  'culture' => ['interval' => 1],
  'civilization' => ['interval' => 1],
  'economy' => ['interval' => 10],       // Every 10 ticks
  'politics' => ['interval' => 20],
  'war' => ['interval' => 50],
  'ecology' => ['interval' => 1],
  'meta' => ['interval' => 1],
]
```

## 3. API ENDPOINTS FOR TICK ADVANCEMENT

**POST /api/worldos/simulation/advance** (Protected)
```json
Request: {"universe_id": 123, "ticks": 50}
Response: {"ok": true, "universe_id": 123, "tick": 50, "snapshot": {...}}
Max ticks per request: 1000 (configurable in controller)
```

**POST /api/worldos/worlds/{id}/pulse** (Protected)
```json
Request: {"ticks_per_universe": 5}
Response: {"ok": true, "results": {universe_id => status}}
Effect: Advances ALL active universes in world by N ticks
```

## 4. CONFIGURATION FOR LARGE-SCALE RUNS

File: backend/config/worldos.php

### Critical Settings:

```php
// Scheduler budget
'scheduler' => ['tick_budget' => 10]  // Max universes per cycle

// Entropy control
'entropy_floor' => 0.001  // Min entropy after each tick

// State caching (Performance!)
'state_cache' => [
  'driver' => 'redis',  // null | redis
  'ttl_seconds' => 300,
]

// Snapshot storage
'snapshot' => [
  'archive_driver' => 's3',  // Archive old snapshots
  'prefix' => 'worldos/snapshots'
]

// Rust engine
'simulation_engine_grpc_url' => 'http://engine:50052'
'simulation_tick_driver' => 'rust_only'

// Slow tick intervals
'planetary_climate' => ['tick_interval' => 500]
'geological' => ['tick_interval' => 5000]
```

## 5. PERFORMANCE BOTTLENECKS & OPTIMIZATION

### Key Bottlenecks:
1. Database snapshots (50,000 INSERTs for 10,000 ticks on 5 universes)
2. Narrative generation (LLM calls on every pulse - 30s timeout)
3. Rust gRPC latency
4. Fork detection & axiom mutation every tick
5. State vector serialization (large JSON blobs)

### Optimization for >5000 ticks:
```bash
# Batch command with optimization
php artisan worldos:simulation-batch {id} \
  --ticks=50000 \
  --chunk=500 \                     # Larger chunks
  --log-every=1000                  # Less frequent logging

# Environment variables
WORLDOS_STATE_CACHE_DRIVER=redis               # Cache state
WORLDOS_NARRATIVE_MIN_TICK_INTERVAL=100        # Skip narrative
WORLDOS_AUTOPOIESIS_TICK_INTERVAL=500          # Less mutations
WORLDOS_SNAPSHOT_ARCHIVE_DRIVER=s3             # Archive snapshots
```

## 6. RUNNING A 5000+ TICK SIMULATION

### Setup:
```bash
php artisan tinker
>>> $world = \App\Models\World::first();
>>> $u = \App\Models\Universe::create(['world_id' => $world->id, 'status' => 'active']);
>>> echo $u->id;
```

### Run:
```bash
# Option A: Batch Command (Recommended)
php artisan worldos:simulation-batch {universe_id} \
  --ticks=5000 \
  --chunk=200 \
  --log-every=500 \
  --output=storage/logs/run_5000.json

# Option B: API Call
curl -X POST http://localhost/api/worldos/simulation/advance \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"universe_id": 1, "ticks": 1000}'
```

### Monitor:
```bash
tail -f storage/logs/run_5000.json | jq '.[-1]'

php artisan tinker
>>> $u = \App\Models\Universe::find(1);
>>> echo $u->current_tick;
```

## 7. KEY FILES & LOCATIONS

| Component | Path |
|-----------|------|
| SimulationSupervisor | app/Modules/Simulation/Core/Supervisor/SimulationSupervisor.php |
| EngineDriver | app/Modules/Simulation/Core/Supervisor/EngineDriver.php |
| AdvanceSimulationAction | app/Modules/Simulation/Actions/AdvanceSimulationAction.php |
| Batch Command | app/Modules/Simulation/Console/Commands/WorldosSimulationBatchCommand.php |
| Orchestrator | app/Modules/Simulation/Services/Core/ImplicitOrchestratorService.php |
| API Routes | app/Modules/WorldOS/routes/api.php |
| Universe Controller | app/Modules/WorldOS/Http/Controllers/UniverseController.php |
| Config | config/worldos.php |
| Tests | tests/Feature/WorldosSimulationTest.php |

## 8. SUMMARY

| Aspect | Detail |
|--------|--------|
| Best for >5000 ticks | worldos:simulation-batch --chunk=500 |
| API max ticks | 1000 per request |
| Tick loop | SimulationSupervisor::execute() line 50-110 |
| Rust call | EngineDriver::advance() → gRPC to engine:50052 |
| Snapshot frequency | Every tick |
| Main bottleneck | Narrative generation + DB I/O |
| Optimization | Redis cache + disable narrative + larger chunks |
| Fork detection | RuntimePipeline each tick |
| Entropy floor | 0.001 configurable |

