# WorldOS V6 - Quick Reference for Large-Scale Simulations

## 🚀 TL;DR: Run a 5000+ Tick Simulation

```bash
# 1. Create universe (one-time)
php artisan tinker
>>> $world = \App\Models\World::first();
>>> $u = \App\Models\Universe::create([
      'world_id' => $world->id,
      'name' => 'Test Run',
      'status' => 'active'
    ]);
>>> exit

# 2. Run 10,000 ticks in optimized batch
php artisan worldos:simulation-batch {universe_id} \
  --ticks=10000 \
  --chunk=500 \
  --log-every=1000 \
  --output=results.json

# 3. Analyze metrics
tail -20 results.json | jq
```

---

## ⚡ Command Comparison

| Command | Use Case | Max Ticks | Output |
|---------|----------|-----------|--------|
| `worldos:simulation-batch` | Large runs (5K+) | ∞ | JSON/CSV metrics |
| `worldos:sim [id] --ticks=N` | Manual testing | ~100 | Console + chronicle |
| `simulation:advance-v3 --ticks=N` | Multi-universe | 100s | Console table |
| `worldos:run-continuous` | Background daemon | ∞ | Console loop |
| `worldos:demo-scenario` | Quick demo | 25 | Console stages |
| **API POST /api/worldos/simulation/advance** | Programmatic | 1000 | JSON response |

---

## 🎯 Batch Command Optimization

```bash
# SLOW (too many DB roundtrips)
php artisan worldos:simulation-batch 1 --ticks=10000 --chunk=10

# FAST (optimal balance)
php artisan worldos:simulation-batch 1 --ticks=10000 --chunk=500

# LOGGING: Less frequent = faster
--log-every=1000  # Log 10 times for 10k ticks (vs 100x per tick)
```

**Why chunking matters:**
- Chunk=10: 1000 DB roundtrips for 10k ticks
- Chunk=500: 20 DB roundtrips for 10k ticks
- Chunk=1000: 10 DB roundtrips for 10k ticks

---

## 🔧 Performance Tuning (Environment Variables)

```bash
# Enable Redis state caching (avoids DB reads)
WORLDOS_STATE_CACHE_DRIVER=redis

# Skip narrative generation (saves 30s per pulse)
WORLDOS_NARRATIVE_MIN_TICK_INTERVAL=999999

# Less frequent mutations
WORLDOS_AUTOPOIESIS_TICK_INTERVAL=1000

# Archive old snapshots to S3
WORLDOS_SNAPSHOT_ARCHIVE_DRIVER=s3
```

Add to `.env`:
```
WORLDOS_STATE_CACHE_DRIVER=redis
WORLDOS_NARRATIVE_MIN_TICK_INTERVAL=999999
WORLDOS_AUTOPOIESIS_TICK_INTERVAL=1000
```

---

## 📊 Understanding Ticks & Snapshots

**What happens each tick:**
```
1. EngineDriver calls Rust gRPC (~50-100ms per tick)
   ├─ Get state from Redis cache (if enabled)
   ├─ Prepare zones, institutions, macro_agents
   └─ Call engine.advance() gRPC

2. Save snapshot to DB
   ├─ universe_snapshots.insert()
   ├─ Calculate engine_health score
   └─ Add metrics: entropy, stability, etc.

3. Run post-tick pipeline
   ├─ Fork detection
   ├─ Axiom mutations (every N ticks)
   └─ Events firing

4. Optional: Generate narrative
   ├─ LLM call (expensive! ~30 seconds)
   └─ Save to chronicles table
```

**Config:** See `backend/config/worldos.php` line 177-186:
```php
'tick_pipeline' => [
    'actor' => ['interval' => 1],       // Run every tick
    'economy' => ['interval' => 10],    // Run every 10 ticks
    'war' => ['interval' => 50],        // Run every 50 ticks
]
```

---

## 🔍 Monitoring Progress

**While running (in another terminal):**
```bash
# Watch metrics stream
tail -f storage/logs/run_5000.json | jq '.[-1]'

# Or periodic checks
watch -n 5 'php artisan tinker <<< "echo \App\Models\Universe::find(1)->current_tick;"'
```

**After completion:**
```bash
php artisan tinker
>>> $data = json_decode(file_get_contents('storage/logs/run_5000.json'), true);
>>> echo "Total snapshots: " . count($data);
>>> $last = end($data);
>>> echo "Final tick: {$last['tick']}, entropy: {$last['entropy']}";
>>> echo "Mean pressure: {$last['mean_pressure']}, variance: {$last['variance_pressure']}";
```

---

## 🚨 Troubleshooting

**Simulation stalls or is slow:**
- Check: `WORLDOS_STATE_CACHE_DRIVER=redis` enabled?
- Check: Narrative generation running? Set `WORLDOS_NARRATIVE_MIN_TICK_INTERVAL=999999`
- Check: Fork detection bottleneck? Increase `WORLDOS_AUTOPOIESIS_TICK_INTERVAL`
- Monitor: `tail -f storage/logs/laravel.log | grep "Simulation:"`

**Out of memory:**
- Reduce chunk size: `--chunk=100` instead of 500
- Clear old snapshots: Archive to S3 with `WORLDOS_SNAPSHOT_ARCHIVE_DRIVER=s3`
- Check: `ps aux | grep php` for memory usage

**Snapshots not saving:**
- Ensure DB connection: `php artisan migrate:status`
- Check: `\App\Models\UniverseSnapshot::count()` increasing?

---

## 📁 Key Files to Know

```
backend/app/Modules/Simulation/
├── Core/Supervisor/
│   ├── SimulationSupervisor.php     ← Main tick loop (line 50-110)
│   ├── EngineDriver.php              ← Calls Rust engine
│   └── SnapshotManager.php            ← Saves snapshots
├── Console/Commands/
│   ├── WorldosSimulationBatchCommand.php  ← USE THIS
│   ├── RunDemoScenario.php
│   └── WorldosSimCommand.php
├── Actions/
│   └── AdvanceSimulationAction.php    ← Facade for supervisor
└── Services/
    └── Core/ImplicitOrchestratorService.php  ← runBatch() for multiple universes

backend/config/worldos.php
└── ALL config settings (entropy_floor, scheduler, caching, etc.)

backend/app/Modules/WorldOS/
└── Http/Controllers/UniverseController.php
    └── advance() method (API endpoint)
```

---

## 📈 Expected Performance

**Baseline (unoptimized):**
- ~100-200ms per tick (Rust engine gRPC + DB save)
- 10,000 ticks ≈ 16-33 minutes

**Optimized (Redis + no narrative):**
- ~50-100ms per tick (cached state, no LLM calls)
- 10,000 ticks ≈ 8-16 minutes

**Scaling:** Mostly linear with tick count; CPU bottleneck is Rust engine.

---

## 🎓 Example: Full Simulation Workflow

```bash
# Step 1: Create world & universe
php artisan tinker
>>> $mv = \App\Models\Multiverse::firstOrCreate(['slug' => 'default']);
>>> $world = \App\Models\World::create([
      'multiverse_id' => $mv->id,
      'name' => 'LargeScale',
      'slug' => 'largescale',
      'axiom' => ['meta_edicts' => []],
      'world_seed' => ['seed' => 12345],
      'global_tick' => 0,
      'status' => 'active'
    ]);
>>> $u = \App\Models\Universe::create([
      'world_id' => $world->id,
      'multiverse_id' => $mv->id,
      'name' => 'Test Universe',
      'status' => 'active'
    ]);
>>> exit

# Step 2: Run large simulation
php artisan worldos:simulation-batch {u->id} \
  --ticks=50000 \
  --chunk=500 \
  --log-every=5000 \
  --output=bench_50k.json

# Step 3: Analyze results
php artisan tinker
>>> $metrics = json_decode(file_get_contents('bench_50k.json'), true);
>>> echo "Completed: " . end($metrics)['tick'] . " ticks";
>>> echo "Avg entropy: " . number_format(array_sum(array_column($metrics, 'entropy')) / count($metrics), 4);

# Step 4: (Optional) Replay specific ticks
>>> $u = \App\Models\Universe::find({u->id});
>>> $snap = \App\Models\UniverseSnapshot::where('universe_id', $u->id)->where('tick', 25000)->first();
>>> echo json_encode($snap->state_vector, JSON_PRETTY_PRINT);
```

---

## 🔗 Related Reading

- **Full Guide**: See `WORLDOS_V6_SIMULATION_GUIDE.md`
- **Tick Advancement**: SimulationSupervisor line 50-110
- **Entropy Floor**: EngineDriver line 130-141
- **Config Reference**: config/worldos.php

