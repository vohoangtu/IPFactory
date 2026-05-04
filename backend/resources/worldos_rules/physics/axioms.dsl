# WorldOS V6 - Axiomatic Drifts (V2)
# Defines how universal constants evolve based on the state of the universe.

constraints
axioms.entropy_drift_base 0.0001 0.01
axioms.order_decay_rate 0.001 0.1
axioms.pressure_decay 0.9 0.99
axioms.innovation_impact 0.001 0.05

# --- 1. Thermodynamics Axioms ---

rule Evolve_Entropy_Axioms
priority 10
scope global
category thermodynamics
trigger civilizationComplexity
then
    # Base entropy drift increases with complexity
calc target_drift
formula "(0.0003 * (1.0 + civilizationComplexity * 0.1))"
    
    # Smoothly transition current axiom towards target using declarative drift
drift axioms.entropy_drift_base target target_drift speed 0.05

rule Evolve_Order_Axioms
priority 11
scope global
category thermodynamics
trigger entropy
when
entropy > 0.7
then
    # Order decay accelerates in high-entropy environments
calc accelerated_rate
formula "(axioms.order_decay_rate * 1.01)"
    
set axioms.order_decay_rate accelerated_rate

# --- 2. Pressure Dynamics Axioms ---

rule Evolve_Pressure_Decay
priority 20
scope global
category pressure_dynamics
trigger order
when
order > 0.8
then
    # High order makes pressures dissipate faster
calc faster_decay
formula "(axioms.pressure_decay * 0.999)"
    
set axioms.pressure_decay faster_decay

rule Evolve_Innovation_Impact
priority 21
scope global
category innovation_dynamics
trigger civilizationComplexity
then
    # The impact of innovation on pressure diminishes as complexity increases
calc final_impact
formula "(0.01 * (1.0 - civilizationComplexity * 0.2))"
    
set axioms.innovation_impact final_impact
# --- 3. Stability & Equilibrium Axioms ---

rule Stability_Equilibrium_Drift
priority 30
scope global
category meta_physics
trigger entropy
then
    # Stability naturally tends to 1.0 (equilibrium).
    # We slow down this "healing" speed (V9) to allow systems to drift.
calc heal_speed
formula "(0.005 * (1.1 - entropy))"
drift stability_index target 1.0 speed heal_speed

rule Stability_Pressure_From_Stress
priority 31
scope global
category meta_physics
trigger resource_stress
when
resource_stress > 0.6
then
    # High global resource stress actively damages stability
calc stability_hit
formula "(stability_index - 0.002 * resource_stress)"
set stability_index stability_hit
