# WorldOS V6 Simulation Architecture - Visual Overview

## System Architecture Diagram

The WorldOS V6 system consists of:

**ENTRY POINTS:**
- Artisan Commands (worldos:sim, worldos:simulation-batch, worldos:run-continuous)
- REST API (POST /api/worldos/simulation/advance)

**ORCHESTRATION LAYER (Laravel):**
1. AdvanceSimulationAction (facade)
2. SimulationSupervisor (main loop: 50-110 lines)
3. EngineDriver (Rust gRPC caller)
4. StateSynchronizer (entity sync)
5. SnapshotManager (DB persistence)
6. RuntimePipeline (fork detection, axiom mutations)
7. EventDispatcher (narrative, events)

**PERSISTENCE:**
- Database: universe_snapshots table (one row per tick)
- Cache: Redis (optional state_vector caching)

**EXTERNAL SERVICES:**
- Rust Engine (gRPC at engine:50052)
- Narrative LLM (OpenAI API for chronicle generation)

---

## Per-Tick Execution Sequence

```
FOR EACH TICK:

1. PREPARE STATE
   - Load universe state_vector from Redis cache OR DB
   - Ensure zones, institutions, macro_agents exist

2. ENGINE CALL (Rust gRPC)
   - Send state_input + world_config to engine:50052
   - Receive snapshot with updated state_vector
   - Track timing: 50-100ms per tick

3. POST-PROCESS
   - Enforce entropy floor (>= 0.001)
   - Merge custom fields from Laravel engines
   - Validate zones exist

4. SYNCHRONIZE
   - Update entity state from snapshot
   - Persist entity relationships

5. SNAPSHOT PERSISTENCE
   - INSERT into universe_snapshots table
   - Calculate engine_health score
   - Store metrics (entropy, stability, tick_ms)

6. RUNTIME PIPELINE
   - Check pipeline intervals (actor:1, economy:10, war:50)
   - Run decision engine (fork detection)
   - Apply axiom mutations if entropy unstable
   - Fire world events

7. EVENT DISPATCH
   - Fire Laravel events to listeners
   - Listeners include: Narrative generator, Chronicle creator

8. INCREMENT
   - universe.current_tick += 1
   - Continue to next tick or exit
```

---

## Configuration Hierarchy

```
.env (overrides all)
  ↓
backend/config/worldos.php (default values)
  ↓
Application code reads config('worldos.key')
  ↓
Supervisor, EngineDriver, etc. use values

Key settings in worldos.php:
- entropy_floor: 0.001 (minimum)
- state_cache.driver: redis | null
- simulation_tick_driver: rust_only | laravel_kernel
- tick_pipeline[]: intervals for each phase
- autopoiesis.tick_interval: mutation frequency
- narrative.min_tick_interval: chronicle generation rate
```

---

## Database Schema (Simplified)

```
universes
  - id (PK)
  - world_id (FK)
  - current_tick (counter)
  - status: active|halted|archived
  - state_vector: JSON
  - entropy: float
  - created_at

universe_snapshots
  - id (PK)
  - universe_id (FK)
  - tick: int
  - state_vector: JSON (large blob)
  - metrics: JSON {entropy, stability, engine_health, last_tick_ms}
  - created_at

One snapshot per tick = 10,000 snapshots for 10,000 tick run
```

---

## Command Execution Flow

**worldos:simulation-batch {id} --ticks=10000 --chunk=500 --log-every=1000**

1. Parse arguments
2. Load universe from DB
3. LOOP while remaining > 0:
   - Call AdvanceSimulationAction::execute($id, min(500, remaining))
   - This runs the full supervisor 500 times
   - Decrement remaining
   - If tick % 1000 == 0: append metrics to JSON file
4. Write final output file
5. EXIT

Result: JSON file with ~10 metric rows (one per 1000 ticks)

---

## Performance Bottlenecks

1. **Narrative Generation** (WORST)
   - Triggered every tick (if enabled)
   - Each call: ~30 seconds (LLM API call)
   - For 10,000 ticks: +5+ hours!
   - Solution: Disable with WORLDOS_NARRATIVE_MIN_TICK_INTERVAL=999999

2. **Database I/O**
   - 10,000 snapshots = 10,000 INSERTs
   - Chunking reduces this: chunk=500 → 20 INSERT batches
   - Solution: Enable Redis cache, use larger chunks

3. **State Vector Serialization**
   - Large JSON blobs stored per snapshot
   - Repeated serialization/deserialization
   - Solution: Archive old snapshots to S3

4. **Rust gRPC Latency**
   - 50-100ms per tick (unavoidable)
   - Scales linearly
   - 10,000 ticks at 75ms = ~12.5 minutes just gRPC

5. **Fork Detection**
   - RuntimePipeline checks every tick
   - Axiom mutations (if enabled) every N ticks
   - Solution: Increase WORLDOS_AUTOPOIESIS_TICK_INTERVAL

---

## Expected Performance

**Unoptimized (no caching, narrative enabled):**
- Per tick: 150-250ms
- Narrative adds 30s per pulse (explosive cost)
- 10,000 ticks with narrative: 30-50+ minutes

**Optimized (Redis, narrative disabled):**
- Per tick: 50-100ms
- No LLM overhead
- 10,000 ticks: 8-16 minutes

**Key optimization: Disable narrative generation**
- One pulse of narrative can add 30+ seconds
- Set: WORLDOS_NARRATIVE_MIN_TICK_INTERVAL=999999 (never)

---

## Key Files to Remember

| File | Purpose |
|------|---------|
| SimulationSupervisor.php | Main tick loop (line 50-110) |
| EngineDriver.php | Rust gRPC call |
| SnapshotManager.php | Database persistence |
| WorldosSimulationBatchCommand.php | Batch runner |
| UniverseController.php | API endpoint |
| worldos.php | All config |

---

## Monitoring Large Runs

**Real-time:**
```bash
# Watch metrics
tail -f storage/logs/run_5000.json | jq '.[-1]'

# Check progress
php artisan tinker
>>> Universe::find(1)->current_tick
```

**Post-run analysis:**
```bash
php artisan tinker
>>> $data = json_decode(file_get_contents('run_5000.json'), true);
>>> echo "Final tick: " . end($data)['tick'];
>>> echo "Entropy range: " . min(...) . " to " . max(...);
>>> $u = Universe::find(1);
>>> echo "DB snapshots: " . \App\Models\UniverseSnapshot::where('universe_id', $u->id)->count();
```

