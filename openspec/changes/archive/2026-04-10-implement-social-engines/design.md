## Context

WorldOS V6 simulation chay 43 PHP engines moi tick qua PhaseRegistry + orchestrationMap. Trong do 3 engines (Finance, Production, Diplomacy) la stubs tra ve empty result. Chung da duoc register tai KernelServiceProvider (lines 420, 425, 435) nhung chua co logic. Infrastructure da san sang: WorldState, EngineResult, TickContext, DiplomaticTreaty model, va WorldEventType deu ton tai.

## Goals / Non-Goals

**Goals:**
- Implement FinanceEngine: zone-level credit/debt tracking
- Implement ProductionChainEngine: industrial output voi material bonus
- Implement DiplomacyEngine: treaty management + tension calculation
- Them TREATY_EXPIRED event type
- Fix existing test files cho dung namespace

**Non-Goals:**
- Thay doi engine registration (da co san)
- Them database migrations (DiplomaticTreaty table da ton tai)
- Implement complex AI decision-making cho diplomacy (chi la math engine)
- Full PhaseRegistry migration cho 3 engines nay (van o orchestrationMap)

## Decisions

### D1: FinanceEngine — Pure Math, No DB

**Decision:** Finance engine chi dung zone data tu WorldState, khong query DB.

**Algorithm:**
```
for each zone:
  net = economy_surplus - economy_consumption
  credit = max(0, net)
  debt = max(0, -net)
output: civilization.finance = { zones: [{credit, debt}], total_credit, total_debt }
```

**Rationale:** Giong voi GlobalEconomyEngine — pure aggregation tu zone state. Khong can persist rieng vi state_vector da luu.

### D2: ProductionChainEngine — Material Bonus Multiplier

**Decision:** Production = economy_surplus * material_bonus_multiplier * efficiency_factor(0.5).

**Algorithm:**
```
material_bonus_count = sum of all zones' material_bonus_count
material_bonus_multiplier = 1.0 + (material_bonus_count * 0.1)
for each zone:
  industrial_output = economy_surplus * material_bonus_multiplier * 0.5
output: civilization.production = { zones: [{industrial_output}], total_industrial_output, material_bonus_multiplier }
```

**Rationale:** Test file cho thay multiplier = 1.0 khi material_bonus_count = 0, va efficiency_factor = 0.5. Follow existing test expectations.

### D3: DiplomacyEngine — DB Queries + Event Emission

**Decision:** DiplomacyEngine query `DiplomaticTreaty` table, update expired treaties, emit events, va tinh tension.

**Algorithm:**
```
1. Query active treaties for universe_id
2. Expire treaties where ends_at_tick <= current_tick, set is_active = false
3. For each expired treaty, emit TREATY_EXPIRED event
4. Get factions from state
5. For each faction pair (i,j) where i < j:
   ideology_distance = euclidean_distance(faction_i.ideology_vector, faction_j.ideology_vector)
   has_alliance = any active treaty of type ALLIANCE between i and j
   base_tension = ideology_distance * (has_alliance ? 0.5 : 1.0)
6. Output: diplomacy.tensions = { '{id_i}_{id_j}': {ideology_distance, has_alliance, base_tension} }
```

**Rationale:** Test file verify treaty expiry, event emission, va tension formula. DiplomacyEngine can DI inject DiplomaticTreaty model hoac dung DB facade.

### D4: stateChanges Format

**Decision:** Dung direct array format: `$result->stateChanges[] = ['key' => $value]`

**Rationale:** GlobalEconomyEngine dung cung pattern. Test file expect access qua `stateChanges[0]` array.

## Risks / Trade-offs

- **[Risk] Test files dung namespace cu** (e.g. `App\Simulation\...` thay vi `App\Modules\Simulation\...`). → **Mitigation:** Update namespace trong tests de match codebase hien tai.
- **[Risk] DiplomacyEngine query DB moi tick** → **Mitigation:** Chi chay theo `diplomacy_tick_interval` config (default 20 ticks). Treaty query nhe (few records per universe).
- **[Risk] Test `runWithState()` method khong ton tai tren EngineInterface** → **Mitigation:** Test dung `handle()` truc tiep voi TickContext.
