# WorldOS V6 - War Mechanics Rules
# Chuyển đổi từ WarEngine.php

# Rule tính toán Conflict Pressure mỗi chu kỳ
rule Update_Conflict_Pressure
priority 10
scope civilization
when
true
chance 1.0
then
    # conflict_pressure = (1.0 - stability) * 0.5 + (1.0 - min(1.0, surplus / 5.0)) * 0.3
    # Vì DSL chưa hỗ trợ gán phức tạp 1 dòng có ngoặc, ta tính từng bước
set civilization.war.temp_stability_factor (1.0 - civilization.politics.stability)
set civilization.war.temp_stability_factor (civilization.war.temp_stability_factor * 0.5)
    
set civilization.war.temp_surplus_norm (civilization.economy.total_surplus / 5.0)
set civilization.war.temp_surplus_factor (1.0 - civilization.war.temp_surplus_norm)
set civilization.war.temp_surplus_factor (civilization.war.temp_surplus_factor * 0.3)
    
set civilization.war.conflict_pressure (civilization.war.temp_stability_factor + civilization.war.temp_surplus_factor)
clamp civilization.war.conflict_pressure 0.0 1.0

# Rule chuyển đổi War Stages
rule War_Stage_Mobilization_to_Campaign
priority 20
scope civilization
when
civilization.war.war_stage == "mobilization"
civilization.war.conflict_pressure > 0.6
chance 1.0
then
emit_event WAR_STAGE_SHIFTED
set civilization.war.war_stage "campaign"

rule War_Stage_Campaign_to_Battles
priority 20
scope civilization
when
civilization.war.war_stage == "campaign"
civilization.war.conflict_pressure > 0.4
chance 1.0
then
emit_event WAR_STAGE_SHIFTED
set civilization.war.war_stage "battles"

rule War_Stage_to_Negotiation
priority 20
scope civilization
when
civilization.war.conflict_pressure < 0.3
or
civilization.war.war_stage == "battles"
civilization.war.war_stage == "attrition"
chance 1.0
then
emit_event WAR_STAGE_SHIFTED
set civilization.war.war_stage "negotiation"

# Tính toán Combat Power
rule Update_Combat_Power
priority 30
scope civilization
when
civilization.war.army.soldiers > 0
then
    # combatPower = soldiers * training * technology * morale
set civilization.war.army.combat_power civilization.war.army.soldiers
set civilization.war.army.combat_power (civilization.war.army.combat_power * civilization.war.army.training)
set civilization.war.army.combat_power (civilization.war.army.combat_power * civilization.war.army.technology)
set civilization.war.army.combat_power (civilization.war.army.combat_power * civilization.war.army.morale)