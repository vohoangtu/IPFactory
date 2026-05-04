# WorldOS V6 - Cosmic Pressure Rules (V2)
# Handles Material Stress, Secession Pressure, and Social Dynamics

constraints
material_stress 0.0 1.0
secession_pressure 0.0 1.0
social_stagnation 0.0 1.0
fate_pressure 0.0 1.0

# --- 1. Material Stress ---

rule Calculate_Material_Stress
priority 10
scope zone
category physical_dynamics
trigger base_mass
when
    true
then
    calc depletion_ratio
    formula "(base_mass > 0 ? (1.0 - (structured_mass / base_mass)) : 1.0)"
    
    calc target_stress
    formula "(field_entropy * 0.4 + depletion_ratio * 0.3 + (field_entropy * 1.5) * 0.3)"
    
    drift material_stress target target_stress speed 0.1

# --- 2. Secession Pressure ---

rule Calculate_Secession_Pressure
priority 20
scope zone
category social_dynamics
trigger field_fear
when
    true
then
    # Pz = 0.4 * Dz + 0.4 * Sz - 0.2 * Trust_z
    calc target_secession
    formula "(0.4 * cultural_distance + 0.4 * material_stress - 0.2 * field_authority)"
    
    drift secession_pressure target target_secession speed 0.05

# --- 3. Social Stagnation ---

rule Calculate_Social_Stagnation
priority 30
scope global
category social_dynamics
trigger field_order
when
    true
then
    # Stagnation increases with high order and lack of ideation
    calc target_stagnation
    formula "(field_order * 0.7 - field_ideology * 0.3)"
    
    drift social_stagnation target target_stagnation speed 0.02

# --- 4. Fate Pressure ---

rule Calculate_Fate_Pressure
priority 40
scope global
category legend
trigger current_scars_weight
when
    true
then
    # Historical weight creates a pull that resists stability
    calc target_fate
    formula "current_scars_weight * 0.05"
    
    drift fate_pressure target target_fate speed 0.05