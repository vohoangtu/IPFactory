# WorldOS V6 - Meta-History & Long-Cycle Attractors
# Orchestrates macro-historical transitions (Golden Ages, Dark Ages, Singularity)

rule Golden_Age_Attractor
priority 10
scope global
category history
trigger knowledge_core
when
knowledge_core > 0.8
stability_index > 0.7
entropy < 0.3
then
drift axioms.innovation_boost target 1.5 speed 0.01
emit_event HISTORICAL_PHASE_SHIFT
metadata phase "GOLDEN_AGE"
metadata description "KỶ NGUYÊN HOÀNG KIM: Tri thức và sự ổn định đạt mức cộng hưởng, thúc đẩy sự tiến hóa vượt bậc."

rule Dark_Age_Attractor
priority 10
scope global
category history
trigger entropy
when
entropy > 0.8
stability_index < 0.3 or has_scar("WAR_SCAR")
then
drift axioms.order_decay_rate target 0.2 speed 0.05
emit_event HISTORICAL_PHASE_SHIFT
metadata phase "DARK_AGE"
metadata description "THỜI KỲ TỐI TĂM: Sự hỗn loạn (Entropy) và vết sẹo chiến tranh bóp nghẹt các cấu trúc trật tự."

rule Expansion_Limit_Constraint
priority 5
scope global
category history
trigger population
when
population > 1000000
resource_stress > 0.9
then
drift base_mortality target 0.15 speed 0.1
emit_event RESOURCE_LIMIT_REACHED
metadata severity "CRITICAL"
metadata description "GIỚI HẠN SINH TỒN: Quần thể vượt quá khả năng chịu tải của hệ sinh thái."

constraints
axioms.innovation_boost 0.5 2.0
axioms.order_decay_rate 0.001 0.5