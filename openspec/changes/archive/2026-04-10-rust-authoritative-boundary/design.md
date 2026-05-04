## Context

WorldOS V6 simulation tick hiện chạy: Rust gRPC advance() → StateSynchronizer → SnapshotManager → RuntimePipeline (AgentBatchProcessor gRPC + 43 PHP engines via PhaseRegistry + 9 PostSnapshotHandlers). Nhiều PHP engines tính trùng Rust output. Một số engine chạy cả qua RuleStage lẫn PhaseRegistry (double-execution). Config `rust_authoritative` (default false) chỉ gate 3 PostSnapshotHandlers.

## Goals / Non-Goals

**Goals:**
- Establish Rust as authoritative source for zone physics, entropy, actor fields, economy
- Eliminate PHP→Rust computation overlap (PHP engines not overwriting Rust output)
- Eliminate double-execution (RuleStage + PhaseRegistry calling same engine twice)
- Remove stub engines from PhaseRegistry (3 empty engines wasting cycles)
- Gate all PostSnapshotHandlers with rust_authoritative check

**Non-Goals:**
- Implementing new engine logic (Finance, Diplomacy, Production — separate change)
- Migrating PHP engines to Rust (future phase)
- Changing Rust engine behavior
- Removing engine source files (only deregister from PhaseRegistry)

## Decisions

### D1: Engine Authority Classification

Every PHP engine in PhaseRegistry classified into one of 4 categories:

| Category | Meaning | Action khi rust_authoritative=true |
|----------|---------|-----------------------------------|
| SUPPLEMENT | Logic Rust không có | Luôn chạy |
| OVERLAP | Rust đã tính | Skip |
| STUB | Return empty | Remove khỏi registry |
| BRIDGE | PHP wrapper → Rust | Skip (Rust đã xử lý trực tiếp) |

Classification map (43 engines):

**OVERLAP — Disable (10 engines):**
- ClimateEngine (Rust zone physics tính temperature/biome)
- GeologicalEngine (Rust zone state tính elevation/terrain)
- CosmicPressureEngine (Rust tính entropy accumulation)
- MetabolicEngine (FFI call → Rust MetabolismGrid, redundant)
- GlobalEconomyEngine (Rust economy fields)
- MarketEngine (Rust market computation)
- TradeEngine (Rust trade logic)
- WarEngine (Rust calamity detection)
- PsychologyEngine (Rust actor traits/fear)
- InequalityEngine (Rust civilization metrics)

**STUB — Remove from registry (3 engines):**
- FinanceEngine (empty)
- DiplomacyEngine (empty)
- ProductionChainEngine (empty)

**SUPPLEMENT — Keep (21 engines):**
- NarrativeInterpretationEngine, MythogenesisEngine, CausalityEngine, CausalHistoryEngine
- AscensionEngine, IdeologyEngine, CulturalInfluenceEngine, MeaningEngine
- RealityAnchorEngine, MaterialEvolutionEngine, KnowledgeEvolutionEngine
- LivingWorldEngine, AutopoieticEvolutionEngine
- SingularityStabilityEngine, LegitimacyEliteEngine
- CivilizationLongCycleEngine, CivilizationSettlementEngine, CivilizationPhysicsEngine
- IdeaDiffusionEngine, InformationPropagationEngine
- NarrativeConflictEngine, NarrativePropagationEngine

**BRIDGE — Skip (2 engines):**
- PotentialFieldEngine (returns empty, Rust handles)
- ThermodynamicPhaseEngine (DSL-based, Rust handles)

**ALREADY IN RULESTAGE — Deregister from PhaseRegistry (7 engines):**
- MetaAttractorEngine (called in RuleStage lines 82, 96)
- CausalHistoryEngine (RuleStage line 86)
- ResonanceBleedingEngine (RuleStage line 107)
- PostApotheosisEngine (RuleStage line 115)
- OmegaConvergenceEngine (RuleStage line 119)
- HigherDimensionalEngine (RuleStage line 122)
- InfiniteRecursionEngine (RuleStage line 126)
- IdealismEngine (RuleStage line 130)
- SingularityEngine (RuleStage line 134)

NOTE: Engines in RuleStage continue to execute there — only PhaseRegistry double-registration is removed.

### D2: Implementation Mechanism

Add `EngineAuthority` enum (SUPPLEMENT, OVERLAP, BRIDGE) to `LegacyEngineAdapter`. PhaseRegistry checks `rust_authoritative` config and skips OVERLAP/BRIDGE engines.

Alternative considered: Tag engines in config file → rejected (authority is structural, not config-tunable).

### D3: Config Default Change

`rust_authoritative` → `true` by default. Existing deploys can override via `WORLDOS_SIMULATION_RUST_AUTHORITATIVE=false` env var for backward compat.

## Risks / Trade-offs

- [Risk] SUPPLEMENT engines may read stale state keys that Rust now owns → Mitigation: Audit each SUPPLEMENT engine's state reads, ensure they only read keys Rust doesn't write.
- [Risk] RuleStage engines removed from PhaseRegistry may lose PhaseExecutionResult telemetry → Mitigation: RuleStage already runs via SimulationTickPipeline, telemetry captured there.
- [Risk] Backward compat break for deploys relying on PHP overwriting Rust → Mitigation: env var override `WORLDOS_SIMULATION_RUST_AUTHORITATIVE=false`.
