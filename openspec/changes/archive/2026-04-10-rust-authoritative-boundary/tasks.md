## 1. EngineAuthority Enum & LegacyEngineAdapter Update

- [x] 1.1 Create `EngineAuthority` enum (SUPPLEMENT, OVERLAP, BRIDGE) at `backend/app/Modules/Simulation/Enums/EngineAuthority.php`
- [x] 1.2 Update `LegacyEngineAdapter` to accept optional `EngineAuthority` parameter (default SUPPLEMENT)
- [x] 1.3 Add `getAuthority(): EngineAuthority` method to `LegacyEngineAdapter`

## 2. PhaseRegistry Authority-Aware Filtering

- [x] 2.1 Update `PhaseRegistry::getEnginesForPhase()` to accept optional `bool $rustAuthoritative` parameter — skip OVERLAP/BRIDGE engines when true
- [x] 2.2 Update `WorldKernel::executeRegistryPhase()` to pass `config('worldos_simulation.simulation.rust_authoritative')` to PhaseRegistry

## 3. Config Default Change

- [x] 3.1 Change `rust_authoritative` default from `false` to `true` in `config/worldos_simulation.php`

## 4. KernelServiceProvider — Reclassify Engine Registrations

- [x] 4.1 Tag 10 OVERLAP engines with `EngineAuthority::OVERLAP`: ClimateEngine, GeologicalEngine, CosmicPressureEngine, MetabolicEngine, GlobalEconomyEngine, MarketEngine, TradeEngine, WarEngine, PsychologyEngine, InequalityEngine
- [x] 4.2 Tag 2 BRIDGE engines with `EngineAuthority::BRIDGE`: PotentialFieldEngine, ThermodynamicPhaseEngine
- [x] 4.3 Remove 3 STUB engines from PhaseRegistry: FinanceEngine, DiplomacyEngine, ProductionChainEngine
- [x] 4.4 Remove 9 RuleStage-duplicate engines from PhaseRegistry: MetaAttractorEngine, CausalHistoryEngine, ResonanceBleedingEngine, PostApotheosisEngine, OmegaConvergenceEngine, HigherDimensionalEngine, InfiniteRecursionEngine, IdealismEngine, SingularityEngine

## 5. PostSnapshotHandler Gating

- [x] 5.1 Audit all 9 PostSnapshotHandlers — identify which check `rust_authoritative` and which don't
- [x] 5.2 Add `rust_authoritative` guard to all PostSnapshotHandlers that don't already have it

## 6. Tests

- [x] 6.1 Unit test: PhaseRegistry skips OVERLAP engines when rust_authoritative=true
- [x] 6.2 Unit test: PhaseRegistry returns all engines when rust_authoritative=false
- [x] 6.3 Unit test: LegacyEngineAdapter stores and returns EngineAuthority
- [x] 6.4 Verify no RuleStage-duplicate engines appear in PhaseRegistry registration

## 7. Cleanup & Documentation

- [x] 7.1 Update `backend/app/Modules/Simulation/README.md` with authority model documentation
- [x] 7.2 Update `.dev_status.md` with change status
