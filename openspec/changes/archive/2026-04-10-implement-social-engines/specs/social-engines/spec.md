## ADDED Requirements

### Requirement: FinanceEngine computes zone-level credit and debt
The FinanceEngine SHALL compute credit and debt for each zone based on `economy_surplus` and `economy_consumption`. A zone with positive net (surplus > consumption) SHALL have credit equal to the net and debt of 0. A zone with negative net SHALL have debt equal to the absolute net and credit of 0. The engine SHALL output `civilization.finance` with per-zone data and totals.

#### Scenario: Zone with positive net produces credit
- **WHEN** a zone has economy_surplus=100 and economy_consumption=50
- **THEN** the zone SHALL have credit=50 and debt=0

#### Scenario: Zone with negative net produces debt
- **WHEN** a zone has economy_surplus=20 and economy_consumption=80
- **THEN** the zone SHALL have credit=0 and debt=60

#### Scenario: Totals are aggregated across all zones
- **WHEN** zone 0 has credit=50, debt=0 and zone 1 has credit=0, debt=60
- **THEN** total_credit SHALL be 50 and total_debt SHALL be 60

### Requirement: ProductionChainEngine computes industrial output per zone
The ProductionChainEngine SHALL compute industrial output for each zone as `economy_surplus * material_bonus_multiplier * 0.5`. The material_bonus_multiplier SHALL be `1.0 + (total_material_bonus_count * 0.1)` where total_material_bonus_count is the sum of all zones' material_bonus_count. Output SHALL be `civilization.production` with per-zone data, total_industrial_output, and material_bonus_multiplier.

#### Scenario: Production with no material bonus
- **WHEN** zones have material_bonus_count=0 and economy_surplus=[100, 20]
- **THEN** material_bonus_multiplier SHALL be 1.0
- **THEN** industrial_output for zone 0 SHALL be 50 (100*1.0*0.5)
- **THEN** industrial_output for zone 1 SHALL be 10 (20*1.0*0.5)
- **THEN** total_industrial_output SHALL be 60

#### Scenario: Production with material bonus
- **WHEN** total material_bonus_count across zones is 5
- **THEN** material_bonus_multiplier SHALL be 1.5 (1.0 + 5*0.1)

### Requirement: DiplomacyEngine manages treaty lifecycle
The DiplomacyEngine SHALL query active DiplomaticTreaty records for the universe. Treaties where `ends_at_tick <= current_tick` SHALL be marked `is_active = false`. For each expired treaty, the engine SHALL emit a TREATY_EXPIRED event with source_civ_id and target_civ_id.

#### Scenario: Treaty not yet expired
- **WHEN** current tick is 90 and treaty ends_at_tick is 100
- **THEN** treaty SHALL remain is_active=true
- **THEN** no TREATY_EXPIRED event SHALL be emitted

#### Scenario: Treaty expires at tick
- **WHEN** current tick is 100 and treaty ends_at_tick is 100
- **THEN** treaty SHALL be set to is_active=false in database
- **THEN** a TREATY_EXPIRED event SHALL be emitted

### Requirement: DiplomacyEngine calculates faction tensions
The DiplomacyEngine SHALL calculate tensions between all faction pairs using Euclidean distance of ideology_vector. For each pair (i,j), base_tension SHALL be `ideology_distance * (has_alliance ? 0.5 : 1.0)`. Output SHALL be `diplomacy.tensions` keyed by `'{id_i}_{id_j}'`.

#### Scenario: Factions with alliance have reduced tension
- **WHEN** faction 1 and 2 have ideology vectors [0.8,0.2,0.5] and [0.7,0.3,0.5]
- **WHEN** an active ALLIANCE treaty exists between them
- **THEN** has_alliance SHALL be true
- **THEN** base_tension SHALL be ideology_distance * 0.5

#### Scenario: Factions without alliance have full tension
- **WHEN** faction 1 and 3 have ideology vectors [0.8,0.2,0.5] and [0.1,0.9,0.1]
- **WHEN** no ALLIANCE treaty exists between them
- **THEN** has_alliance SHALL be false
- **THEN** base_tension SHALL be ideology_distance * 1.0

### Requirement: TREATY_EXPIRED event type exists
The WorldEventType class SHALL define a TREATY_EXPIRED constant mapped to topic 'diplomacy'.

#### Scenario: Event type is available
- **WHEN** DiplomacyEngine needs to emit a treaty expiry event
- **THEN** WorldEventType::TREATY_EXPIRED SHALL be available as a valid event type
