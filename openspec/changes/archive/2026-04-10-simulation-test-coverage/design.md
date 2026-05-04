## Context

Audit cho thay 13% engine coverage va <1% service coverage. Existing tests: PhaseRegistryTest, AbstractWorldOSEngineTest, EngineResultTest, WorldKernelV2Test, StateLoaderTest, StateWriterTest, SocialEngineTest, FinanceEngineTest, DiplomacyEngineTest. Can them tests cho cac gaps lon nhat.

## Goals / Non-Goals

**Goals:**
- Test representative engines tu moi category (Social, Meta, Environment, Physics)
- Test cac core services co complex logic
- Dat 30%+ engine test coverage (tu 13%)
- Dam bao tat ca engine handle() methods duoc goi voi valid input ma khong crash

**Non-Goals:**
- 100% coverage (se la change rieng)
- Test cac engines chi la thin wrapper/adapter
- Mock Rust gRPC calls (can Docker)
- Test PostSnapshotHandlers (da tested qua integration)

## Decisions

### D1: Smoke Test Pattern cho Engines
**Decision:** Tao "smoke test" — goi handle() voi minimal valid WorldState + TickContext, assert khong throw exception va return EngineResult. Nhanh, lightweight, bat duoc crashes/typos.

### D2: Focus Group
**Decision:** Test theo thu tu priority:
1. Core Social engines (GlobalEconomy, Market, Trade, Inequality — co real logic)
2. Environment engines (Climate, Geological)
3. Biological engines (AutopoieticEvolution, EcologicalCollapse)
4. Meta engines — chon 5 representative tu 47 (complex nhat)

### D3: Test Structure
**Decision:** 1 test file per engine category (SocialEngineSmokeTest, MetaEngineSmokeTest, etc.) de giam file sprawl. Moi engine co 1-2 test methods.

## Risks / Trade-offs

- **[Risk] Engines co hidden dependencies (DB, config)** → Mitigation: Dung mock/stub patterns, skip engines can Docker.
- **[Risk] Some engines may have bugs discovered by tests** → Mitigation: Document bugs, khong fix trong change nay (fix rieng).
