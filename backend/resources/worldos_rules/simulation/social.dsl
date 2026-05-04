/*
@rule_graph_id: social_topology_v2
@priority: 15
@trigger: ON_TICK
*/

rule Field_Interference
scope: global
when:
fields.fear > 0.6
then:
drift fields.knowledge by -0.05
drift fields.authority by 0.1

rule Resonance_Stability
scope: global
when:
resonance_field > 0.7
then:
drift fields.order by 0.05
drift entropy by -0.02

rule Ethos_Calculation
scope: global
then:
    // Calculate social dimensions for Ethos vector
set rigidity = fields.authority * 0.6 + fields.fear * 0.4
set openness = fields.knowledge * 0.7 + (1.0 - fields.fear) * 0.3
set resilience = (1.0 - entropy) * 0.5 + resonance_field * 0.5
set spirituality = fields.meaning * 0.8 + resonance_field * 0.2
set solidarity = resonance_field * 0.6 + (1.0 - entropy) * 0.4
    
    // Engine metadata
set drift_epsilon = 0.002
set diffusion_beta = 0.008