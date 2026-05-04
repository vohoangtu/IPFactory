# WorldOS V6 - Historical Chronicles (V2)
# Evaluates historical events to determine their "Mythic Weight" and long-term impact.

rule Evaluate_Historical_Trauma
priority 90
scope global
category legend
trigger event_type
when
event_type == "COLLAPSE" or event_type == "GENOCIDE" or event_type == "EXTINCTION"
then
calc mythic_weight
formula "event_intensity * (1.0 + causal_integrity_debt * 0.5)"
    
when
mythic_weight > 0.8
then
emit_event CREATE_WORLD_SCAR
metadata type "TRAUMA"
metadata weight mythic_weight
metadata duration "PERMANENT"
      # A trauma scar increases secession pressure for generations
drift pressures.fate_pressure target 0.5 speed 0.1

rule Evaluate_Cultural_Golden_Age
priority 91
scope global
category legend
trigger event_type
when
event_type == "ASCENSION" or event_type == "GREAT_INVENTION" or event_type == "EPOCH_TRANSITION"
then
calc heritage_weight
formula "event_intensity * field_knowledge_field"
    
when
heritage_weight > 0.7
then
emit_event CREATE_HERITAGE
metadata type "GLORY"
metadata weight heritage_weight
      # Heritage reduces entropy decay in the long run
drift axioms.entropy_drift_base target 0.0002 speed 0.01

rule Fate_Resonance_Evaluation
priority 95
scope global
category legend
trigger current_scars
then
    # Historical scars act as an "Attractor" for future tragedy or glory
calc fate_pull
formula "sum(current_scars.weight) * 0.01"
    
drift pressures.fate_pressure target fate_pull speed 0.05