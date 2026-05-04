## Why

3 social engines (FinanceEngine, ProductionChainEngine, DiplomacyEngine) hien la stubs tra ve `EngineResult::empty()`. Chung van duoc register trong WorldKernel orchestrationMap nhung khong lam gi. Can implement logic thuc de simulation co du du lieu kinh te va ngoai giao — la nen tang cho narrative generation va actor decision-making.

## What Changes

- **FinanceEngine**: Implement zone-level credit/debt tracking dua tren `economy_surplus` va `economy_consumption`. Output: `civilization.finance`.
- **ProductionChainEngine**: Implement industrial output per zone voi material bonus multiplier. Output: `civilization.production`.
- **DiplomacyEngine**: Implement treaty expiry, ideology distance calculation, va tension tracking giua factions. Output: `diplomacy.tensions`. Emit `TREATY_EXPIRED` event. Query/update `DiplomaticTreaty` model.
- **WorldEventType**: Them `TREATY_EXPIRED` constant.
- **Tests**: Cap nhat existing feature test files cho dung namespace hien tai.

## Capabilities

### New Capabilities
- `social-engines`: Implementation logic cho FinanceEngine, ProductionChainEngine, va DiplomacyEngine — zone-level economics, industrial production, va diplomatic tension tracking.

### Modified Capabilities
- `unified-kernel`: Engines da duoc register, chi can fill logic trong `handle()`. Khong thay doi registration.

## Impact

- `backend/app/Modules/Simulation/Core/Engines/Social/FinanceEngine.php` — implement handle()
- `backend/app/Modules/Simulation/Core/Engines/Social/ProductionChainEngine.php` — implement handle()
- `backend/app/Modules/Simulation/Core/Engines/Social/DiplomacyEngine.php` — implement handle() + DI
- `backend/app/Modules/Simulation/Core/Events/WorldEventType.php` — add TREATY_EXPIRED
- `backend/tests/Feature/Simulation/FinanceEngineTest.php` — update namespace + fix assertions
- `backend/tests/Feature/Simulation/DiplomacyEngineTest.php` — update namespace + fix assertions
