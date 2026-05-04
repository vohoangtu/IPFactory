# WorldOS V6 - Historical Phase Transitions (V2)
# Handles transitions between historical epochs based on field accumulation and thresholds.

rule Transition_Genesis_to_Order
priority 100
scope global
category phase_transitions
trigger epoch
when
epoch == "genesis"
then
when
field_order > 0.6
then
emit_event PHASE_TRANSITION
drift axioms.order_decay_rate target 0.005 speed 1.0

rule Transition_to_Renaissance
priority 110
scope global
category phase_transitions
trigger field_knowledge
when
field_knowledge > 0.6
field_belief > 0.5
then
emit_event PHASE_TRANSITION
drift axioms.innovation_impact target 0.02 speed 1.0

rule Transition_to_Industrial
priority 120
scope global
category phase_transitions
trigger field_ideology
when
field_knowledge > 0.8
tech_level > 0.7
then
emit_event PHASE_TRANSITION
drift axioms.entropy_drift_base target 0.005 speed 0.1