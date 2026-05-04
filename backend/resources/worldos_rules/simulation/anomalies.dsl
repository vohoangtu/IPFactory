# WorldOS V6 - Anomaly & Disaster Rules
# Triggers and defines side-effects for unclassifiable anomalies

# --- ANOMALIES ---

rule Anomaly_Biological_Hivemind
priority 50
scope global
when
random_chance < 0.01
has_zones == true
then
emit_event SPAWN_ANOMALY
metadata anomaly_type "biological_hivemind"
metadata severity (random_chance * 100.0)
metadata material_stress_inc 0.4
metadata agent_order 1.0
metadata description "Tâm thức bầy đàn sinh học đang đồng hóa các dạng sống."

rule Anomaly_Spatial_Fracture
priority 50
scope global
when
entropy > 0.85
random_chance < 0.005
then
emit_event SPAWN_ANOMALY
metadata anomaly_type "spatial_fracture"
metadata severity 0.9
metadata coherence_inc 0.2
metadata scar_intensity 0.99
metadata description "Vết nứt không gian - Thời gian bị bẻ cong cục bộ."

rule Anomaly_Axiom_Duplication
priority 50
scope global
when
instability_gradient > 0.75
random_chance < 0.01
then
emit_event SPAWN_ANOMALY
metadata anomaly_type "axiom_duplication"
metadata severity 1.0
metadata description "Sự nhân bản tiên đề: Các quy luật cơ bản đang chồng chéo lên nhau."

# --- NATURAL DISASTERS ---

rule Disaster_Trigger
priority 60
scope zone
when
material_stress > 0.6
random_chance < 0.1
then
emit_event NATURAL_DISASTER_TRIGGERED
set intensity (0.4 + random_float_0_6)
metadata intensity intensity
metadata disaster_limit 20
metadata description "Thảm họa tự nhiên bùng nổ do áp lực vật chất vượt ngưỡng."