/*
@rule_graph_id: leadership_evolution_v1
@priority: 90
@trigger: ON_LAW_STEP
*/

rule Innovation_Tendency_Nudge
scope: global
when:
innovation.stagnation_score < 0.3
fields.power > 0.6
then:
drift world_rules.innovation_tendency by 0.01

rule Stabilizing_Order
scope: global
when:
stability > 0.8
then:
drift world_rules.order_tendency by 0.005
drift world_rules.entropy_tendency by -0.005

rule Chaos_Axiom_Shift
scope: global
when:
entropy > 1.4
random_chance < 0.05
then:
drift world_rules.entropy_tendency by 0.05
emit WORLD_RULES_MUTATED { cause: "entropy_surge"