## 1. Prerequisites

- [x] 1.1 Add `TREATY_EXPIRED` constant to `WorldEventType` class with topic mapping 'diplomacy'

## 2. FinanceEngine Implementation

- [x] 2.1 Implement `FinanceEngine::handle()` — zone-level credit/debt from economy_surplus and economy_consumption, output `civilization.finance`

## 3. ProductionChainEngine Implementation

- [x] 3.1 Implement `ProductionChainEngine::handle()` — industrial output per zone with material_bonus_multiplier, output `civilization.production`

## 4. DiplomacyEngine Implementation

- [x] 4.1 Implement `DiplomacyEngine::handle()` — treaty expiry (query + update DiplomaticTreaty), event emission, and faction tension calculation

## 5. Tests

- [x] 5.1 Fix namespace imports in `FinanceEngineTest.php` to match current codebase structure
- [x] 5.2 Fix namespace imports in `DiplomacyEngineTest.php` to match current codebase structure and adapt to `handle()` API
- [x] 5.3 Add unit test for ProductionChainEngine with material bonus scenarios

## 6. Cleanup

- [x] 6.1 Update `.dev_status.md` with implementation status
