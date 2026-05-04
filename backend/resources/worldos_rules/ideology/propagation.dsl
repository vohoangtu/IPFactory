# WorldOS V6 - Ideology Propagation Rules
# Handles Cultural Diffusion between zones and Autonomous Drift (§9.2)

# --- GLOBAL SETTINGS ---
# DIMENSIONS: tradition, innovation, trust, violence, respect, myth

# Rule for Autonomous Drift: Small random nudges to prevent stagnation
rule Cultural_Drift
priority 10
scope zone
then
    # DRIFT_EPSILON = 0.001 (from PHP logic)
    # Since DSL might not have a built-in 'random' yet that returns a value to use in expressions 
    # as easily as PHP, we'll assume the engine provides 'random_nudge' or we use a small bias 
    # if certain conditions are met, or simply model the trend.
    
    # Example: If stability is high, innovation drifts lower, tradition higher
when
stability_index > 0.8
then
adjust tradition 0.0005
adjust innovation -0.0005
      
when
entropy > 0.6
then
adjust violence 0.001
adjust trust -0.001

# Rule for Diffusion (Inter-zone influence)
# Note: Engine needs to handle the neighbor aggregation, but rules can specify the rate.
rule Cultural_Diffusion
priority 20
scope zone
when
    # Proxy for "exposure to neighbors"
global_entropy > 0.3
then
    # DIFFUSION_BETA = 0.005
    # The actual math (neighbor_val - current_val) * beta is usually handled by the 
    # 'potential field' or 'diffusion' operator in advanced DSLs.
    # For now, we emit an intent or use a built-in command if the Rust engine supports it.
emit_event CULTURE_DIFFUSION_TICK