## Why

Simulation module hien co test coverage rat thap: 13% engines, <1% services. Cac component quan trong nhu SimulationTickOrchestrator, HolographicCompressionService, va phan lon Meta engines chua co test. Can gia tang coverage cho cac thanh phan critical de dam bao simulation hoat dong dung khi refactor tiep.

## What Changes

- Them unit tests cho cac core engines chua duoc test: representative engines tu moi category (Social, Meta, Physics, Biological, Environment)
- Them unit tests cho core runtime services: HolographicCompressionService, SimulationTickOrchestrator
- Them integration test cho WorldKernel + PhaseRegistry end-to-end pipeline
- Focus vao testable logic — pure computation engines truoc, sau do den engines co side effects

## Capabilities

### New Capabilities
- `simulation-test-suite`: Comprehensive unit + integration tests covering core simulation engines and runtime services.

### Modified Capabilities

## Impact

- `backend/tests/Unit/Simulation/` — them 4-6 test files moi
- `backend/tests/Feature/Simulation/` — them 1-2 integration tests
- Khong thay doi production code — chi them tests
