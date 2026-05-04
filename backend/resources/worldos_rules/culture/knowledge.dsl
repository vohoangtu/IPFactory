# WorldOS V10 - Knowledge Evolution & Paradigm Shifts
# Quy tắc định hình các bước nhảy vọt tri thức.

rule Paradigm_Astrometry_Breakthrough
priority 20
scope global
category knowledge
trigger tick
when
fields.knowledge > 0.3
meta.knowledge_graph.name == "ASTROMETRY"
then
    # Đồng bộ tri thức thiên văn củng cố vũ trụ quan
drift fields.complexity target 0.6 speed 0.05
emit_event SCIENTIFIC_BREAKTHROUGH
metadata paradigm "ASTROMETRY"
metadata description "Quần thể đã bắt đầu hiểu được vị thế của mình trong vũ trụ bao la."

rule Causal_Logic_Paradigm_Shift
priority 25
scope global
category knowledge
trigger tick
when
fields.knowledge > 0.7
meta.knowledge_graph.name == "CAUSAL_LOGIC"
then
    # Logic nhân quả tối thượng phá vỡ các xiềng xích giáo điều
drift meta.meaning_systems.coherence target 0.3 speed 0.1
drift fields.complexity target 0.9 speed 0.05
emit_event PARADIGM_SHIFT
metadata paradigm "CAUSAL_LOGIC"
metadata description "Sự trỗi dậy của logic nhân quả thuần túy đang thách thức những hệ thống niềm tin cổ hủ."

rule Quantum_Axioms_Apotheosis
priority 30
scope global
category knowledge
trigger tick
when
fields.knowledge > 0.9
meta.knowledge_graph.name == "QUANTUM_AXIOMS"
then
    # Chạm đến các tiên đề lượng tử - Bước chuẩn bị cho sự thăng hoa (Apotheosis)
drift fields.stability_index target 1.0 speed 0.2
emit_event QUANTUM_REVELATION
metadata description "Bản chất xác suất của thực tại đã được giải mã. Cánh cửa đến Peak state đang mở ra."