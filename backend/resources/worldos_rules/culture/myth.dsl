# WorldOS V10 - Mythogenesis & Narrative Archetypes
# Quy tắc chuyển hóa sự kiện thành huyền thoại tập thể.

rule Archetype_Martyrdom
priority 15
scope global
category culture
trigger historical_fact
when
fact.category == "WAR"
fact.impact > 0.8
then
drift meta.active_myths.symbolic_power target 1.2 speed 0.05
emit_event MYTH_RESONANCE
metadata archetype "MARTYR"
metadata description "Sự hy sinh anh dũng đã trở thành biểu tượng vĩnh cửu của lòng trung kiên."

rule Archetype_Promethean_Spark
priority 15
scope global
category culture
trigger historical_fact
when
fact.category == "DISCOVERY"
fact.impact > 0.85
then
drift meta.active_myths.symbolic_power target 1.3 speed 0.02
emit_event MYTH_RESONANCE
metadata archetype "CREATOR"
metadata description "Ngọn lửa tri thức mới đã thắp sáng màn đêm u tối của sự vô tri."

rule Myth_Drift_Decay
priority 5
scope global
category culture
trigger tick
when
active_myths.count > 0
then
    # Tự động hóa sự suy tàn của huyền thoại nếu không được nhắc lại
decay meta.active_myths.belief_strength factor 0.99