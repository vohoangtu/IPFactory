# --- WorldOS Multiverse Scheduling DSL ---
# Prioritizing Universes based on Information Density and Causal Complexity

rule Multiverse_Priority_Calculation
priority 10
scope global
then
    # 1. Complexity Score (Normalized)
    # count_zones provided by host
set complexity (count_zones / 12.0)
set complexity (clamp complexity 0.0 1.0)
    
    # 2. Civilization Score (Normalized)
    # count_settlements provided by host
set civilization (count_settlements / 8.0)
set civilization (clamp civilization 0.0 1.0)
    
    # 3. Entropy Inverse (Stability factor)
set entropy_score (1.0 - entropy)
set entropy_score (clamp entropy_score 0.0 1.0)
    
    # 4. Novelty Calculation (RMS deviation of fields from 0.5)
    # fields provided: f_survival, f_power, f_wealth, f_knowledge, f_meaning
set nov_sum 0.0
set nov_sum (nov_sum + ((f_survival - 0.5) * (f_survival - 0.5)))
set nov_sum (nov_sum + ((f_power - 0.5) * (f_power - 0.5)))
set nov_sum (nov_sum + ((f_wealth - 0.5) * (f_wealth - 0.5)))
set nov_sum (nov_sum + ((f_knowledge - 0.5) * (f_knowledge - 0.5)))
set nov_sum (nov_sum + ((f_meaning - 0.5) * (f_meaning - 0.5)))
    
set novelty (sqrt (nov_sum / 5.0))
set novelty (clamp novelty 0.0 1.0)
    
    # 5. Final Priority Score (Weighted Sum)
    # Weights can be adjusted here without code changes
set w_novelty 0.25
set w_complexity 0.30
set w_civilization 0.25
set w_entropy 0.20
    
set priority_score ((novelty * w_novelty) + (complexity * w_complexity) + (civilization * w_civilization) + (entropy_score * w_entropy))
set priority_score (clamp priority_score 0.0 1.0)
    
    # 6. Edge of Chaos Booster
    # Boost if entropy is in the critical transition range (0.7 - 0.9)
if (entropy > 0.7) then
if (entropy < 0.9) then
set priority_score (priority_score + 0.1)
set priority_score (clamp priority_score 0.0 1.0)