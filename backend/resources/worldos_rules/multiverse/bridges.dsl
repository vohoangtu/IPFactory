# Causal Bridges DSL 🌉✨
# Phase 62: Định nghĩa quy tắc di cư và trao đổi nhân quả đa vũ trụ.

rule heroic_migration_on_collapse
when
active_attractor == 'DARK_AGE' && stability_index < 0.3 && meta.active_causal_bridges.count > 0
then
        # Khi thực tại sụp đổ, các Anh hùng tìm đường sang dòng thời gian ổn định hơn.
        # Tác động: Giảm dân số anh hùng nhưng bảo tồn "Legacy" cho multiverse.
        
pressure "heroic_evacuation" add 0.5
        
emit_event MULTIVERSE_MIGRATION
metadata description "Các Anh hùng đang vượt qua cửa ngõ nhân quả để tìm kiếm một thực tại ổn định hơn."

rule resonance_knowledge_leak
when
meta.active_causal_bridges.count > 0
then
        # Các vũ trụ cộng hưởng cao sẽ tự động chia sẻ tri thức (Field Density).
foreach bridge in meta.active_causal_bridges
drift fields.knowledge target 1.0 speed (bridge.resonance * 0.01)
drift fields.meaning target 1.0 speed (bridge.resonance * 0.005)

rule causal_contamination
when
entropy > 0.8 && meta.active_causal_bridges.count > 0
then
        # Cảnh báo: Entropy có thể rò rỉ qua cầu nhân quả nếu không được kiểm soát.
foreach bridge in meta.active_causal_bridges
pressure "entropy_leak" add (bridge.flow_rate * 0.1)