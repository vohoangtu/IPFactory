# WorldOS V6 - Society & Governance Dynamics
# Handles Politics, Law Evolution, and Social Inequality

# --- 1. Political Metrics ---

rule Calculate_Politics
priority 10
scope global
when
    total_population > 0
then
    set temp_surplus_ratio (total_surplus / total_population);
    set temp_surplus_ratio (clamp temp_surplus_ratio 0.0 1.0);
    
    set stability (0.5 + (0.2 * temp_surplus_ratio));
    set stability (stability + (0.1 * (det_random - 0.5)));
    set stability (clamp stability 0.0 1.0);
    
    set military_power ((total_population * 0.0002) + (0.3 * stability));
    
    set economic_power (total_surplus / 1000.0);
    set economic_power (clamp economic_power 0.0 1.0);
    
    set legitimacy (0.4 + (0.3 * stability));
    set legitimacy (legitimacy + (0.2 * economic_power));
    set legitimacy (clamp legitimacy 0.0 1.0);

# --- 2. Governance Evolution (Level-9) ---

rule Governance_Stability
priority 20
scope global
when
    true
then
    # stability_drift = (legitimacy * 0.05) - (inequality * 0.04) - (corruption * 0.03)
    set s_drift (legitimacy * 0.05);
    set s_drift (s_drift - (inequality * 0.04));
    set s_drift (s_drift - (corruption * 0.03));
    
    set stability (stability + s_drift);
    set stability (clamp stability 0.1 1.0);

rule Legitimacy_Drift
priority 21
scope global
when
    entropy > 0.7
then
    # legitimacy += (stability * 0.02) - (0.01 if entropy high)
    set l_drift (stability * 0.02);
    set l_drift (l_drift - 0.015);
      
    set legitimacy (legitimacy + l_drift);
    set legitimacy (clamp legitimacy 0.0 1.0);

rule Legitimacy_Alert
priority 22
scope global
when
    legitimacy < 0.3
then
    # Emit event if legitimacy low
    emit_event GOVERNANCE_CRISIS;

# --- 2. Social Dynamics & Ethos ---

rule Calculate_Ethos
priority 40
scope global
when
    true
then
    set rigidity (clamp (1.0 - entropy) 0.0 1.0);
    set openness entropy;
    set resilience stability;
    set spirituality ((entropy + stability) / 2.0);
    set solidarity stability;
    
    # Diffusion & Drift parameters for PHP engine
    metadata drift_epsilon 0.001;
    metadata diffusion_beta 0.005;
    
    emit_event ETHOS_CALCULATED;
    metadata rigidity rigidity;
    metadata openness openness;
    metadata resilience resilience;