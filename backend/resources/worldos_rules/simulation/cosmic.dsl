/*
@rule_graph_id: cosmic_evolution_v1
@priority: 100
@trigger: ON_COSMIC_TICK
*/

rule Resonance_Stabilization
scope: global
when:
meta.resonance > 0.1
then:
drift structural_coherence by 0.02
drift entropy by -0.01

rule Reality_Omen_Shift
scope: global
when:
meta.omen.type != "Natural Flow"
then:
drift structural_coherence by meta.omen.sci_modifier
drift entropy by meta.omen.entropy_modifier

rule Heat_Death_Trigger
scope: global
when:
entropy > 1.8
structural_coherence < 0.1
then:
emit UNIVERSE_HEAT_DEATH { cause: "entropy_max"
set status "collapsed"

rule Axiom_Mutation_Chaos
scope: global
when:
entropy > 1.5
random_chance < 0.05
then:
emit AXIOM_MUTATION { type: "quantum_leak"

rule Sovereignty_Knowledge_Harvest
scope: global
when:
civilization.knowledge > 0.9
resonance_field > 0.8
then:
emit SOVEREIGNTY_ELEVATION { level: "ascended"
drift structural_coherence by 0.1

rule Bekenstein_Bound_Dilation
scope: global
when:
cosmic.data_mass > 0.8
then:
    # Khi mật độ thông tin quá cao, thời gian bị giãn nở (giảm drift tốc độ)
drift entropy by 0.05
emit TIME_DILATION_MARKER { power: cosmic.data_mass

rule Reality_Crystallization
scope: global
when:
cosmic.data_mass > 0.95
then:
    # Thông tin hóa hoàn toàn thực tại: Vật chất đóng băng, chỉ còn ý tưởng trôi nổi
set status "crystallized"
emit TERMINAL_HORIZON { description: "Thực tại đã đạt tới giới hạn lưu trữ dữ liệu vĩnh cửu."

rule Singularity_Event_Horizon
scope: global
when:
cosmic.data_mass > 1.2
then:
    # Điểm kỳ dị thông tin: Hợp nhất toàn bộ manifold vào một điểm
emit OMEGA_SINGULARITY { type: "informational_collapse"
set status "collapsed"