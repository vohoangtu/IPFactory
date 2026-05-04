## 1. Foundation — Enums, Value Objects, Base Classes

- [x] 1.1 Tạo `SimulationPhase` backed enum (Environment=1, Life=2, Mind=3, Social=4, Meta=5) tại `backend/app/Modules/Simulation/Enums/SimulationPhase.php`
- [x] 1.2 Tạo `EngineResult` value object với properties: mutations, metrics (duration_ms, entities_affected), skipped, reason tại `backend/app/Modules/Simulation/ValueObjects/EngineResult.php`
- [x] 1.3 Tạo `TickContext` value object chứa tick metadata (tick_number, universe_id, world_id, config) tại `backend/app/Modules/Simulation/ValueObjects/TickContext.php`
- [x] 1.4 Tạo `AbstractWorldOSEngine` base class với abstract methods: name(), phase(), execute() và default methods: priority(), isEnabled() tại `backend/app/Modules/Simulation/Engines/AbstractWorldOSEngine.php`
- [x] 1.5 Tạo `EngineInterface` contract tại `backend/app/Contracts/EngineInterface.php` mà AbstractWorldOSEngine implements

## 2. PhaseRegistry — Engine Registration System

- [x] 2.1 Tạo `PhaseRegistry` class có methods: register(AbstractWorldOSEngine), getEnginesForPhase(SimulationPhase), getAllPhases() tại `backend/app/Modules/Simulation/Services/Kernel/PhaseRegistry.php`
- [x] 2.2 Implement priority sorting trong PhaseRegistry — engines cùng phase sắp xếp theo priority(), equal priority thì alphabetical by name()
- [x] 2.3 Implement duplicate engine name detection — throw exception khi register engine trùng name
- [x] 2.4 Implement isEnabled() filtering — PhaseRegistry skip engines có isEnabled() return false
- [x] 2.5 Cập nhật `KernelServiceProvider` để register PhaseRegistry singleton và đăng ký engines từng phase

## 3. State Management Refactor

- [x] 3.1 Tạo `StateLoader` class extract logic load từ StateManager::load() — load state_vector, decompress, reconstruct WorldState tại `backend/app/Modules/Simulation/Services/State/StateLoader.php`
- [x] 3.2 Tạo `StateWriter` class extract logic save từ StateManager::save() — batch save actors, institutions, universe tại `backend/app/Modules/Simulation/Services/State/StateWriter.php`
- [x] 3.3 Implement batch delete dead actors trong StateWriter — sử dụng `Actor::whereIn('id', $ids)->delete()` thay vì loop
- [x] 3.4 Wrap toàn bộ StateWriter::save() trong DB::transaction(), throw StateWriteException nếu fail
- [x] 3.5 Tạo `StateCacheManager` class extract cache logic từ StateManager tại `backend/app/Modules/Simulation/Services/State/StateCacheManager.php`
- [x] 3.6 Refactor StateManager thành facade class — delegate load() → StateLoader, save() → StateWriter, mark methods `@deprecated`
- [x] 3.7 Tạo `StateWriteException` custom exception tại `backend/app/Modules/Simulation/Exceptions/StateWriteException.php`

## 4. WorldKernel Refactor

- [x] 4.1 Refactor WorldKernel constructor nhận PhaseRegistry, EventDispatcher, StateLoader, StateWriter (≤ 10 params)
- [x] 4.2 Refactor WorldKernel::execute() sử dụng PhaseRegistry thay vì gọi engines trực tiếp — iterate 5 phases theo order
- [x] 4.3 Tạo `PhaseExecutionResult` để collect EngineResult từ mỗi engine trong phase
- [x] 4.4 Cập nhật `PipelineServiceProvider` binding cho WorldKernel mới

## 5. SimulationKernel Deprecation

- [x] 5.1 Audit toàn bộ SimulationKernel — liệt kê tất cả effects/logic chưa có trong WorldKernel
- [x] 5.2 Di chuyển logic thiếu từ SimulationKernel sang WorldKernel (bridge via LegacyEngineAdapter; Fiber parallel + throttling deferred to full migration)
- [x] 5.3 Đánh dấu `@deprecated` trên SimulationKernel class và tất cả public methods
- [x] 5.4 Cập nhật config `simulation_tick_driver` default value sang `world_kernel`

## 6. Engine Migration (Phase-by-Phase)

- [x] 6.1 Migrate 2-3 engines đơn giản nhất sang extend AbstractWorldOSEngine (proof of concept)
- [x] 6.2 Migrate Environment phase engines (ClimateEngine, GeologicalEngine, MetabolicEngine, CosmicPressureEngine, RealityAnchorEngine, MaterialEvolutionEngine) via LegacyEngineAdapter → PhaseRegistry
- [x] 6.3 Migrate Life phase engines (LivingWorldEngine, AutopoieticEvolutionEngine) via LegacyEngineAdapter → PhaseRegistry
- [x] 6.4 Migrate Mind phase engines (PsychologyEngine, IdeaDiffusionEngine, NarrativeConflictEngine, InformationPropagationEngine, MeaningEngine, KnowledgeEvolutionEngine) via LegacyEngineAdapter → PhaseRegistry
- [x] 6.5 Migrate Social phase engines (GlobalEconomyEngine, PoliticsEngine, CultureEngine, PowerStructureEngine, MarketEngine, TradeEngine, DiplomacyEngine, FinanceEngine, ProductionChainEngine, InequalityEngine, LegitimacyEliteEngine, CivilizationSettlementEngine, CivilizationPhysicsEngine, CivilizationLongCycleEngine, CulturalInfluenceEngine, ThermodynamicPhaseEngine) via LegacyEngineAdapter → PhaseRegistry
- [x] 6.6 Migrate Meta phase engines (MythogenesisEngine, CausalityEngine, IdeologyEngine, AscensionEngine, CausalHistoryEngine, SingularityStabilityEngine, NarrativePropagationEngine, NarrativeInterpretationEngine, WarEngine) via LegacyEngineAdapter → PhaseRegistry

## 7. Unit Tests

- [x] 7.1 Tạo test cho PhaseRegistry — register, retrieve by phase, priority ordering, duplicate detection tại `backend/tests/Unit/Simulation/PhaseRegistryTest.php`
- [x] 7.2 Tạo test cho WorldKernel — phase execution order, engine skip, priority ordering tại `backend/tests/Unit/Simulation/WorldKernelV2Test.php`
- [x] 7.3 Tạo test cho StateLoader — full state loading, cache hit path tại `backend/tests/Unit/Simulation/StateLoaderTest.php`
- [x] 7.4 Tạo test cho StateWriter — batch save, batch delete, transaction rollback tại `backend/tests/Unit/Simulation/StateWriterTest.php`
- [x] 7.5 Tạo test cho AbstractWorldOSEngine — default priority, default isEnabled tại `backend/tests/Unit/Simulation/AbstractWorldOSEngineTest.php`
- [x] 7.6 Tạo test cho EngineResult — construction, skipped status tại `backend/tests/Unit/Simulation/EngineResultTest.php`

## 8. Feature Tests & gRPC Integration

- [x] 8.1 Tạo feature test cho EngineDriver::advance() với mock gRPC server tại `backend/tests/Feature/Simulation/EngineDriverTest.php`
- [x] 8.2 Tạo feature test cho gRPC timeout handling tại `backend/tests/Feature/Simulation/GrpcTimeoutHandlingTest.php`
- [x] 8.3 Tạo feature test end-to-end: AdvanceSimulationAction → WorldKernel → StateWriter tại `backend/tests/Feature/Simulation/AdvanceSimulationEndToEndTest.php`

## 9. Documentation & Cleanup

- [x] 9.1 Document gRPC contract — Rust engine responsibilities vs Laravel pipeline responsibilities
- [x] 9.2 Cập nhật module README tại `backend/app/Modules/Simulation/README.md`
- [x] 9.3 Đánh dấu `@deprecated` cho `simulation_tick_driver` + `simulation_kernel_post_tick` config toggle trong `config/worldos_simulation.php` (sẽ remove hoàn toàn khi SimulationKernel bị xóa)
- [x] 9.4 Remove SimulationKernel class (done: migrated SnapshotManager, SimulationReplayService → WorldKernel, removed EngineServiceProvider binding, updated docblocks, deleted class)
- [x] 9.5 Cập nhật `.dev_status.md` với trạng thái refactor hiện tại
