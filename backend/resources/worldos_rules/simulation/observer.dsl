/*
@rule_graph_id: quantum_observer_v1
@priority: 95
@trigger: ON_OBSERVATION_STEP
*/

rule Observation_Load_Decay
scope: global
when:
meta.is_observed == false
meta.observation_load > 0
then:
    # Natural decay of observation load (entropy recovery)
drift meta.observation_load by -0.05

rule Reality_Saturation_Alert
scope: global
when:
meta.observation_load > 7.5
then:
emit REALITY_SATURATION_ALERT
message: "Cảnh báo: Độ bão hòa thực tại đạt mức tới hạn. Kết cấu không gian đang bị nén chặt."

rule Spontaneous_Wavefunction_Collapse
scope: global
when:
meta.observation_load > 5.0
random_chance < 0.05
then:
    # In DSL, we can't directly call an Action, 
    # but we can emit an event that a Projector/Listener handles, 
    # or better, manifest impacts directly via drift.
    
drift entropy by -0.01
drift stability_index by 0.02
emit NARRATIVE_EVENT { type: "QUANTUM_STUTTER"