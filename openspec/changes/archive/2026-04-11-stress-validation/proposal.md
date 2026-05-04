## Why

Infrastructure cho stress testing da san sang (SimulationSupervisor, EngineDriver, TickManifest, SimulationReplayService, TickMetricsService) nhung chua co stress test thuc su. Test lon nhat hien tai chi 3 ticks. Can tao artisan command de chay 5000+ ticks voi progress reporting, memory monitoring, va deterministic replay verification de validate simulation stability truoc khi production.

## What Changes

- **Artisan command:** `worldos:stress-test` — chay N ticks voi progress bar, memory tracking, checkpoint intervals, va summary report
- **Replay verification test:** Feature test chay M ticks, replay, va assert deterministic match
- **Neo4j health check:** Artisan command `worldos:health-check` de verify Neo4j + Redis + DB connectivity
- **Performance baseline:** Luu baseline metrics (avg ms/tick, memory peak, skip rate) de compare across runs

## Capabilities

### New Capabilities
- `stress-test-harness`: Artisan commands va tests cho large-scale simulation validation

### Modified Capabilities

## Impact

- `backend/app/Modules/Simulation/Console/Commands/StressTestCommand.php` — new command
- `backend/app/Modules/Simulation/Console/Commands/HealthCheckCommand.php` — new command
- `backend/tests/Feature/Simulation/StressValidationTest.php` — new test
- `backend/tests/Feature/Simulation/DeterministicReplayTest.php` — new test
