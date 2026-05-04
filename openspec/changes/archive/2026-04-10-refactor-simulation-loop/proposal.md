## Why

Simulation Loop hiện tại có hai kernel song song (WorldKernel và SimulationKernel) không rõ cái nào là canonical, StateManager là god object xử lý quá nhiều trách nhiệm (load/save 20+ thao tác mỗi hàm), và các class core như WorldKernel nhận 50+ dependency qua constructor. Điều này làm code khó bảo trì, khó test, và dễ phát sinh regression khi mở rộng. Cần refactor ngay vì dự án đang scale thêm nhiều engine mới và test coverage chỉ ~5%.

## What Changes

- **Thống nhất Kernel**: Giữ `WorldKernel` làm canonical orchestrator duy nhất, deprecate và loại bỏ `SimulationKernel` legacy
- **Tách StateManager**: Phân rã `StateManager` god object thành 3 class riêng biệt: `StateLoader`, `StateWriter`, `StatePersister` theo Single Responsibility Principle
- **Giảm Constructor Dependencies**: Refactor `WorldKernel` và `SimulationTickPipeline` sử dụng Phase Registry pattern thay vì inject 50+ dependency trực tiếp
- **Chuẩn hóa Engine Interface**: Tạo base `AbstractWorldOSEngine` class thống nhất interface cho tất cả engine
- **Fix N+1 Query**: Batch delete dead actors trong StateManager thay vì xóa từng cái
- **Tăng Test Coverage**: Thêm unit tests cho WorldKernel, StateManager, và gRPC integration
- **Làm rõ gRPC Contract**: Document rõ ràng trách nhiệm Rust engine vs Laravel pipeline

## Capabilities

### New Capabilities
- `unified-kernel`: Thống nhất WorldKernel làm orchestrator canonical duy nhất với phase registry pattern, loại bỏ dual-kernel confusion
- `state-management-v2`: Tách StateManager thành StateLoader/StateWriter/StatePersister riêng biệt, fix N+1 queries, hỗ trợ batch operations
- `engine-interface-standard`: Base AbstractWorldOSEngine class chuẩn hóa lifecycle và interface cho tất cả simulation engines
- `simulation-test-suite`: Test suite chuyên cho simulation loop — unit tests cho kernel, state management, và gRPC integration

### Modified Capabilities
<!-- Không có specs hiện có cần thay đổi -->

## Impact

- **Backend Code**: Toàn bộ `backend/app/Modules/Simulation/` — đặc biệt `Services/Core/`, `Services/Kernel/`, `Services/Pipeline/`, `Services/State/`
- **Service Providers**: `SimulationServiceProvider` và tất cả sub-providers (`KernelServiceProvider`, `PipelineServiceProvider`, `EngineServiceProvider`) cần cập nhật binding
- **gRPC Integration**: `EngineDriver`, `GrpcSimulationEngineClient` — không thay đổi API nhưng cần document contract rõ ràng
- **Config**: `config/worldos.php` — loại bỏ `simulation_tick_driver` toggle giữa hai kernel, simplify config flags
- **Cross-module**: Các module khác (World, Narrative, SocialGraph) sử dụng simulation events cần verify không bị breaking changes
- **Tests**: Thêm ~30-50 test cases mới trong `tests/Unit/Simulation/` và `tests/Feature/Simulation/`
- **Dependencies**: Không thêm package mới, chỉ refactor internal code
