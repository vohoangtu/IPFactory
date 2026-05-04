/*
@rule_graph_id: collective_innovation_v1
@priority: 80
@trigger: ON_INNOVATION_STEP
*/

rule Accumulate_Stagnation
scope: global
when:
institutions.count > 5
then:
drift innovation.stagnation_score by (institutions.count * 0.002)

rule Creative_Breakthrough_Resonance
scope: global
when:
fields.knowledge > 0.8
meta.resonance > 0.5
random_chance < 0.1
then:
drift fields.knowledge by 0.05
drift innovation.stagnation_score by -0.02
emit NARRATIVE_EVENT { type: "SCIENTIFIC_REVOLUTION"

rule Stagnation_Entropy_Bleed
scope: global
when:
innovation.stagnation_score > 0.7
then:
drift entropy by 0.02
drift fields.wealth by -0.01

rule Destroy_Inefficient_Order
scope: global
when:
innovation.stagnation_score > 0.95
entropy > 1.2
then:
drift innovation.stagnation_score by -0.5
drift entropy by 0.1
emit NARRATIVE_EVENT { type: "INSTITUTIONAL_REFORM"