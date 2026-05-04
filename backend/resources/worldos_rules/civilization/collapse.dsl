# WorldOS V6 - Civilization Collapse Rules
# Manages thresholds and reset parameters for societal collapse

rule Detect_Civilization_Collapse
priority 100
scope civilization
when
entropy > (civilization.politics.stability * 1.4)
then
emit_event CIVILIZATION_COLLAPSE_TRIGGERED
    
    # Structural metadata for PHP engine
metadata field_power_penalty 0.4
metadata field_survival_boost 0.25
metadata field_knowledge_loss 0.3
metadata field_stability_drop 0.45
metadata attractor_strength_bonus 0.05
metadata base_attractor_strength 0.5
    
    # Applied via DSL side-effects for immediate state update
add civilization.politics.stability -0.45
clamp civilization.politics.stability 0.0 1.0
    
add civilization.fields.power -0.4
clamp civilization.fields.power 0.0 1.0
    
add civilization.fields.survival 0.25
clamp civilization.fields.survival 0.0 1.0
    
set civilization.fields.knowledge (civilization.fields.knowledge * 0.7)
    
set civilization.collapse_at_tick tick