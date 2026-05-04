## Context

WorldOS V6 simulation loop hiện tại đã phát triển organically qua nhiều iteration, dẫn đến kiến trúc có nhiều vấn đề cấu trúc:

- **Dual Kernel**: `WorldKernel` (5-phase model mới) và `SimulationKernel` (effect-based legacy) cùng tồn tại, config flag `simulation_tick_driver` switch giữa hai cái. Developer không biết extend cái nào.
- **StateManager God Object**: Một class duy nhất chịu trách nhiệm load state (8+ entity types), save state, delete dead actors (N+1), compress/decompress holographic state — vi phạm SRP nghiêm trọng.
- **Constructor Hell**: `WorldKernel` nhận 50+ params, `SimulationTickPipeline` nhận 17+ params qua constructor — làm testing và maintenance cực khó.
- **Inconsistent Engine Interface**: 100+ engine singletons không chia sẻ base class hay lifecycle chuẩn.
- **Sparse Tests**: Chỉ 9 test files cho ~50,000 LOC simulation code (~5% coverage).

**Stakeholders**: Backend team, Engine (Rust) team, bất kỳ module nào subscribe simulation events.

**Constraints**:
- PHP 8.3, Laravel 13
- Không thay đổi gRPC API của Rust engine
- Backward compatible với existing simulation data
- Tất cả commands phải chạy trong Docker containers

## Goals / Non-Goals

**Goals:**
- Thống nhất thành một kernel canonical duy nhất (WorldKernel)
- Tách StateManager thành các class đơn trách nhiệm
- Giảm constructor dependencies xuống dưới 10 params cho mỗi class core
- Chuẩn hóa engine interface qua AbstractWorldOSEngine base class
- Fix N+1 query khi xóa dead actors
- Đạt ≥40% test coverage cho simulation core
- Document rõ ràng Rust vs Laravel responsibilities

**Non-Goals:**
- Không thay đổi Rust engine code hoặc gRPC protobuf
- Không thay đổi 5-phase model (Environment → Life → Mind → Social → Meta)
- Không refactor frontend hoặc Python services
- Không optimize performance (benchmark riêng sau)
- Không thay đổi database schema
- Không thêm features mới — chỉ refactor internal structure

## Decisions

### 1. Giữ WorldKernel, loại bỏ SimulationKernel

**Quyết định**: WorldKernel với 5-phase model là canonical. SimulationKernel sẽ deprecated và code chuyển sang WorldKernel.

**Lý do**: WorldKernel có kiến trúc rõ ràng hơn (5 phases với 15 primitive rules), structured orchestration. SimulationKernel dùng effect-based model phức tạp hơn và dùng PHP Fibers chưa validate performance.

**Alternatives considered**:
- *Merge cả hai*: Quá phức tạp, hai mental models khác nhau
- *Giữ SimulationKernel*: Effect-based model khó reason about, Fibers overhead chưa benchmark
- *Abstract cả hai sau một interface*: Thêm indirection không cần thiết, sẽ chỉ dùng một cái

**Migration**: Đánh dấu `@deprecated` trên SimulationKernel, di chuyển logic còn thiếu sang WorldKernel, config flag `simulation_tick_driver` mặc định `world_kernel` và sẽ bị xóa ở version tiếp.

### 2. Phase Registry Pattern cho WorldKernel

**Quyết định**: Thay vì inject 50+ engine qua constructor, WorldKernel sẽ sử dụng `PhaseRegistry` — mỗi phase tự đăng ký engines của mình.

**Lý do**: Constructor với 50+ params là untestable. PhaseRegistry cho phép add/remove engine theo phase mà không thay đổi WorldKernel constructor.

```
WorldKernel(PhaseRegistry $registry, EventDispatcher $events, StateManager $state)
```

Mỗi Phase class (EnvironmentPhase, LifePhase, etc.) nhận engines riêng:
```
EnvironmentPhase(EntropyEngine, PhysicsEngine, ZoneEngine, ...)
```

**Alternatives considered**:
- *Service Locator*: Anti-pattern, giấu dependencies
- *Container auto-resolve*: Implicit, khó trace
- *Tagged services*: Laravel tagged bindings — viable nhưng ít type-safe hơn PhaseRegistry

### 3. Tách StateManager thành 3 class

**Quyết định**: Phân rã thành:
- `StateLoader` — Load state từ DB/cache, decompress, reconstruct WorldState
- `StateWriter` — Batch save actors, institutions, universe; batch delete dead actors
- `StateCacheManager` — Cache/invalidation logic, holographic compression

**Lý do**: StateManager hiện tại vi phạm SRP — load, save, cache, delete trong 1 class. Tách ra giúp test từng phần riêng biệt và fix N+1 query dễ hơn.

**Alternatives considered**:
- *Giữ nguyên, chỉ extract methods*: Không giải quyết SRP issue
- *Tách thành 2 (Reader/Writer)*: Cache logic vẫn bị trộn
- *Repository pattern cho từng entity*: Quá nhiều classes, over-engineering cho use case này

### 4. AbstractWorldOSEngine Base Class

**Quyết định**: Tạo abstract base class cho tất cả engines:

```php
abstract class AbstractWorldOSEngine implements EngineInterface {
    abstract public function name(): string;
    abstract public function phase(): SimulationPhase;
    abstract public function execute(WorldState $state, TickContext $ctx): EngineResult;
    
    public function priority(): int { return 0; }
    public function isEnabled(WorldConfig $config): bool { return true; }
}
```

**Lý do**: 100+ engines không có shared interface. Mỗi engine implement khác nhau, lifecycle không chuẩn. Base class enforce consistency và cho phép PhaseRegistry tự động phân loại engines.

**Alternatives considered**:
- *Interface only*: Không có default implementations cho priority, enabled check
- *Trait*: Không enforce structure, chỉ mixin behavior
- *Decorator pattern*: Over-complex cho standardization goal

### 5. Batch Delete cho Dead Actors

**Quyết định**: Thay thế loop `foreach($deadActors as $actor) { $actor->delete(); }` bằng `Actor::whereIn('id', $deadActorIds)->delete()`.

**Lý do**: N+1 query. 100 dead actors = 100 DELETE queries. Batch = 1 query.

## Risks / Trade-offs

- **[Risk] SimulationKernel có logic chưa di chuyển** → Mitigation: Audit toàn bộ SimulationKernel effects trước khi deprecate, tạo mapping document effect → WorldKernel phase
- **[Risk] PhaseRegistry thêm indirection** → Mitigation: Clear documentation, IDE-friendly registration, compile-time verification qua unit tests
- **[Risk] StateManager refactor break existing code** → Mitigation: Giữ StateManager facade class delegate sang 3 class mới, backward compatible, deprecate dần
- **[Risk] Engine interface migration effort lớn** → Mitigation: Implement dần — base class trước, migrate engine theo từng phase, không phải tất cả cùng lúc
- **[Risk] Cross-module events có thể bị ảnh hưởng** → Mitigation: Event signatures không thay đổi, chỉ internal orchestration thay đổi
- **[Trade-off] Thêm abstraction layers** → Chấp nhận thêm files/classes để đổi lấy testability và maintainability
- **[Trade-off] Migration period sẽ có deprecated code** → Timeline 2-3 sprints để loại bỏ hoàn toàn

## Migration Plan

1. **Phase 1 (Sprint 1)**: Tạo AbstractWorldOSEngine, PhaseRegistry, tách StateManager — backward compatible, deprecated methods còn hoạt động
2. **Phase 2 (Sprint 2)**: Migrate top-priority engines sang AbstractWorldOSEngine, WorldKernel dùng PhaseRegistry, test suite
3. **Phase 3 (Sprint 3)**: Deprecate SimulationKernel, remove config toggle, migrate remaining engines, cleanup

**Rollback**: Mỗi phase có thể revert độc lập qua git. Config flag `simulation_tick_driver` giữ lại đến Phase 3 cho rollback.

## Open Questions

- Có engine nào trong SimulationKernel dùng PHP Fibers mà cần giữ parallel execution không? Cần benchmark trước khi quyết định.
- StateLoader có cần hỗ trợ lazy loading cho large worlds không? Hiện tại load eager toàn bộ.
- Holographic compression logic nên ở StateCacheManager hay tách riêng thành service?
