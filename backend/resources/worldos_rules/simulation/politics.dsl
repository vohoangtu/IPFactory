/*
@rule_graph_id: politics_dynamics_v2
@priority: 15
@trigger: ON_POLITICS_TICK
*/

rule Stability_Resonance_Sync
scope: global
when:
resonance_field > 0.8
then:
drift stability_index by 0.05
drift civilization.politics.legitimacy by 0.02

rule Elite_Overproduction_Decay
scope: global
when:
civilization.politics.elite_overproduction > 0.1
then:
drift stability_index by -0.1
drift civilization.politics.legitimacy by -0.05
emit EVENT_POLITICAL_TENSION { severity: "rising" };

rule High_Entropy_Unrest
scope: global
when:
entropy > 0.8
then:
drift stability_index by -0.2
emit GOVERNANCE_CRISIS { cause: "entropy_leak" };

rule Legitimacy_Recovery
scope: global
when:
civilization.politics.legitimacy < 0.3
resonance_field > 0.5
then:
drift civilization.politics.legitimacy by 0.05

rule Mythic_Resonance_Legitimacy
scope: global
when:
civilization.politics.social_cohesion > 0.8
then:
    # Sự gắn kết xã hội cao thông qua huyền thoại chung củng cố tính chính danh
drift civilization.politics.legitimacy by 0.1
emit SOCIAL_HARMONY { description: "Huyền thoại chung đã tạo nên sự đồng thuận vĩ đại." };

rule Technocratic_Efficiency
scope: global
when:
civilization.politics.governance_type == "TECHNOCRACY"
fields.knowledge > 0.8
then:
    # Kỹ trị giúp giảm tham nhũng và tăng tính ổn định
drift civilization.politics.corruption by -0.05
drift stability_index by 0.02

rule Theocratic_Stability
scope: global
when:
civilization.politics.governance_type == "THEOCRACY"
civilization.politics.social_cohesion > 0.7
then:
    # Thần quyền duy trì sự ổn định thông qua niềm tin mãnh liệt
drift stability_index by 0.05
drift civilization.politics.legitimacy by 0.03