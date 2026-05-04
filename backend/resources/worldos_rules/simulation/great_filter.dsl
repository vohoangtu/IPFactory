# WorldOS V6 - Great Filter Rules (Macro-evolutionary Crises)
# Defines conditions and effect magnitudes for existential crises

rule Trigger_Singularity_Crisis
priority 200
when
    fields.knowledge > 0.9
    civilization.politics.trust < 0.3
then
    emit_event "CRISIS_SINGULARITY_TRIGGERED"
    metadata intensity 1.0
    metadata actor_kill_pct 0.3
    metadata capacity_penalty 0.2
    metadata description "NGHỊCH LÝ ĐIỂM KỶ TỬ: Công nghệ đột phá vượt xa tầm kiểm soát của đạo đức và niềm tin xã hội."

rule Trigger_Stagnation_Crisis
priority 200
when
    fields.meaning > 0.8
    civilization.politics.avg_capacity < 5.0
then
    emit_event "CRISIS_STAGNATION_TRIGGERED"
    metadata intensity 0.8
    metadata capacity_multiplier 0.4
    metadata memory_multiplier 0.6
    metadata description "SỰ ĐÌNH TRỆ HỆ THỐNG: Truyền thống hủ lậu và bộ máy cồng kềnh đã bóp nghẹt mọi mầm mống đổi mới."

rule Trigger_Void_Breach_Crisis
priority 200
when
    fields.entropy > 0.95
then
    emit_event "CRISIS_VOID_BREACH_TRIGGERED"
    metadata intensity 1.2
    metadata trauma_boost 0.6
    metadata fragmentation_chance 0.5
    metadata description "CÁNH CỬA HƯ VÔ: Entropy đạt mức cực hạn. Ranh giới giữa hiện hữu và hư vô đang tan biến."