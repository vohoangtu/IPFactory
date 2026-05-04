# WorldOS V6 - Physics Core Rules (V2)
# Handles Thermodynamics, Stability, and Cosmic Transitions

# --- 1. Thermodynamics & Entropy ---

rule Calculate_Entropy_Drift
priority 10
scope global
when
    true == true
then
    set total_drift (axioms.entropy_drift_base + (civilizationCount * 0.00001) + (civilizationComplexity * 0.001))
    drift entropy target (entropy + total_drift + random_chaos_noise) speed 1.0

rule Calculate_Order_Decay
priority 11
scope global
when
    true == true
then
    set current_decay (entropy * axioms.order_decay_rate)
    drift order target (order - current_decay) speed 1.0

# --- 2. Pressure Accumulation & Decay ---

rule Evolve_Cosmic_Pressures
priority 15
scope global
when
    true == true
then
    set target_p_innovation (p_innovation * axioms.pressure_decay + innovation * axioms.innovation_impact)
    set target_p_entropy (p_entropy * axioms.pressure_decay + entropy * 0.015)
    set target_p_order (p_order * axioms.pressure_decay + order * 0.01)
    set target_p_myth (p_myth * axioms.pressure_decay + myth * 0.01)
    set target_p_conflict (p_conflict * axioms.pressure_decay + violence * 0.02)
    set target_p_ascension (p_ascension * axioms.pressure_decay + spirituality * 0.012)
    
    drift p_innovation target target_p_innovation speed 1.0
    drift p_entropy target target_p_entropy speed 1.0
    drift p_order target target_p_order speed 1.0
    drift p_myth target target_p_myth speed 1.0
    drift p_conflict target target_p_conflict speed 1.0
    drift p_ascension target target_p_ascension speed 1.0

# --- 3. Phase Transition Pressures ---

rule Physics_Pressures
priority 20
when
    true == true
then
    set target_collapse (entropy * 0.6 + (1.0 - order) * 0.4)
    set target_ascension (order * 0.35 + energyLevel * 0.35 + spirituality * 0.25 - violence * 0.15)
    
    drift collapse_pressure target target_collapse speed 0.1
    drift ascension_pressure target target_ascension speed 0.1

# --- 4. Stability Clamps ---

rule Entropy_Safety_Clamp
priority 30
scope global
when
    entropy > 0.95
then
    drift entropy target 0.94 speed 0.05
    drift collapse_pressure target 1.0 speed 0.01

rule Entropy_Freeze_Prevention
priority 31
scope global
when
    entropy < 0.02
then
    drift entropy target 0.03 speed 1.0

# --- 5. Cosmic Events (Transitions) ---

rule Trigger_Eschaton
priority 100
scope global
when
    entropy >= 0.99
then
    emit_event ESCHATON

rule Trigger_Eschaton_Pressure
priority 101
scope global
when
    collapse_pressure > 0.95
then
    emit_event ESCHATON

rule Trigger_Ascension
priority 110
scope global
when
    ascension_pressure > 0.9
    random_chance < 0.01
then
    emit_event ASCENSION