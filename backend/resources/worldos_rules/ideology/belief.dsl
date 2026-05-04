# WorldOS V6 - Belief Formation Rules
# Handles individual actor belief shifts based on environment and traits (§21)

rule Form_Causal_Trajectory_Belief
priority 50
scope actor
when
    # High logic/knowledge + high anomaly sensitivity
knowledge > 0.8
anomaly_sensitivity > 0.7
    # Or exposure to a specific field
resonance_level > 0.5
then
    # set_path 'beliefs.causal_trajectory' 1.0 (or increment strength)
emit_event ACTOR_BELIEF_SHIFT
    # params: { type: 'causal_trajectory', strength_delta: 0.1 }

rule Form_Mythic_Belief
priority 50
scope actor
when
trauma > 0.6
myth_belief_zone > 0.7
intellect < 0.4
then
emit_event ACTOR_BELIEF_SHIFT
    # params: { type: 'myth', strength_delta: 0.15 }

rule Secular_Shift
priority 50
scope actor
when
knowledge > 0.9
stability_index > 0.9
then
emit_event ACTOR_BELIEF_SHIFT
    # params: { type: 'secular', strength_delta: 0.05 }