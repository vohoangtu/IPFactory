## 1. Stress Test Command

- [x] 1.1 Create `StressTestCommand.php` — `worldos:stress-test {universeId} --ticks=100 --checkpoint=100`
- [x] 1.2 Implement tick loop with progress bar, memory tracking, and checkpoint reporting

## 2. Health Check Command

- [x] 2.1 Create `HealthCheckCommand.php` — `worldos:health-check` verifying DB, Redis, Neo4j

## 3. Feature Tests

- [x] 3.1 Create `StressValidationTest.php` — run 50 ticks, assert no crashes, verify metrics
- [x] 3.2 Create `DeterministicReplayTest.php` — run 20 ticks, replay, assert hash match

## 4. Cleanup

- [x] 4.1 Update `.dev_status.md`
