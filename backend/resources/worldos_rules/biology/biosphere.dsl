# WorldOS V6 - Biosphere & Population Dynamics
# Handles Ecological Collapse, Population growth, and Disease outbreaks

# --- 1. Ecological Stability & Collapse ---

rule Detect_Ecological_Collapse
priority 10
scope global
when
    is_collapse_active == false
    instability_score >= 0.7
then
    emit_event ECOLOGICAL_COLLAPSE_TRIGGERED
    metadata instability_score instability_score
    metadata resource_stress resource_stress
    set collapse_duration (200 + (random_dur * 800))
    set is_collapse_active true

rule Set_Collapse_Famine
priority 11
scope global
when
    is_collapse_active == true
    resource_stress >= 0.6
then
    set collapse_type "famine"

rule Set_Collapse_Random
priority 11
scope global
when
    is_collapse_active == true
    resource_stress < 0.6
then
    # Determine type randomly in PHP or split here
    set collapse_type "predator_crash"

rule Monitor_Collapse_Duration
priority 12
scope global
when
    is_collapse_active == true
    current_tick >= until_tick
then
    set is_collapse_active false
    emit_event ECOLOGICAL_COLLAPSE_ENDED

# --- 2. Population & Biology Metrics ---

rule Population_Dynamics_Base
priority 20
when
    true == true
then
    set base_fertility 0.05
    set base_mortality 0.02

rule Population_Collapse_Impact
priority 21
when
    is_collapse_active == true
then
    set base_fertility (base_fertility * 0.5)
    set base_mortality (base_mortality * 2.0)

rule Population_Finalize
priority 22
when
    true == true
then
    set fertility (clamp base_fertility 0.0 1.0)
    set mortality (clamp base_mortality 0.0 1.0)

# --- 3. Disease Propagation (SIR Model) ---

rule Disease_SIR_Evolution
priority 30
scope global
when
    is_collapse_active == true
    collapse_type == "disease"
then
    set beta 0.3
    set gamma 0.1
    set d_infected (infected * beta * (susceptible / population))
    set d_recovered (infected * gamma)
    set d_mortality (infected * 0.02)
    add mortality d_mortality
    emit_event PANDEMIC_PROGRESS
    metadata new_infections d_infected
    metadata new_recoveries d_recovered
    metadata new_deaths d_mortality

# --- 4. Agriculture & Food Security ---

rule Agriculture_Production
priority 5
when
    true == true
then
    set tech_mult (1.0 + (tech_level * 2.0))
    set base_prod (land_area * tech_mult * ecological_stability)
    set food_req (population * 0.01)
    set food_surplus (base_prod - food_req)
    set food_security (clamp (base_prod / food_req) 0.0 2.0)
    set production base_prod
    set requirement food_req

rule Famine_Risk_Logic
priority 6
when
    food_security < 0.6
then
    set famine_risk (1.0 - food_security)
    # Random chance handled by engine logic if possible, 
    # or use emit_event with metadata for PHP trigger
    emit_event FAMINE_RISK_CHECK
    metadata risk_level famine_risk

# --- 5. Climate Cycles & Stability ---

rule Climate_Cycle_Evolution
priority 1
when
    true == true
then
    set e_drift (0.01 * (random_chance - 0.5))
    add ecological_stability e_drift

rule Climate_Instability_Check
priority 2
when
    ecological_stability < 0.4
then
    add ecological_stability -0.02
    emit_event CLIMATE_INSTABILITY_WARNING

rule Climate_Finalize
priority 3
when
    true == true
then
    set ecological_stability (clamp ecological_stability 0.0 1.0)