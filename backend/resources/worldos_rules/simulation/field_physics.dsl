# Civilization Field Physics DSL 🪐✨
# Phase 54: Định nghĩa các tương tác giữa các trường lực xã hội cốt lõi.

# -----------------------------------------------------------------------------
# 1. FIELD DIFFUSION & DECAY (Khuếch tán & Phân rã)
# -----------------------------------------------------------------------------

rule global_entropy_diffusion
priority 10
then
        # Entropy luôn có xu hướng tăng và lan tỏa
drift fields.entropy target 0.5 speed 0.01

rule knowledge_decay
priority 10
then
        # Nếu không có duy trì, tri thức sẽ bị mất mát dần (quên lãng)
drift fields.knowledge target 0.1 speed 0.005

# -----------------------------------------------------------------------------
# 2. FIELD INTERACTIONS (Tương tác giữa các trường)
# -----------------------------------------------------------------------------

rule knowledge_to_innovation
when
field_knowledge > 0.5
then
        # Tri thức cao thúc đẩy sáng tạo
pressure "innovation" add (fields.knowledge * 0.4)
drift fields.innovation target 1.0 speed (fields.knowledge * 0.02)

rule belief_vs_knowledge_interference
when
field_belief > 0.7
then
        # Tín ngưỡng quá cao có thể tạo ra sự phản kháng với tri thức mới (Censorship)
pressure "knowledge_resistance" add (fields.belief * 0.3)
drift fields.knowledge target 0.0 speed (fields.belief * 0.01)

rule power_concentration_violence_potential
when
field_power > 0.7
then
        # Quyền lực tập trung cao làm tăng khả năng xảy ra bạo lực (Internal conflict)
pressure "violence" add (fields.power * 0.5)
drift fields.conflict target 1.0 speed 0.01

rule economy_resonance_knowledge
when
field_wealth > 0.6
then
        # Kinh tế phát triển hỗ trợ giáo dục và tri thức
drift fields.knowledge target 1.0 speed (fields.wealth * 0.01)
pressure "meaning" add (fields.wealth * 0.1)

# -----------------------------------------------------------------------------
# 3. EMERGENT PATTERNS (Phát hiện các cấu hình ổn định)
# -----------------------------------------------------------------------------

rule detect_golden_age_pattern
when
field_knowledge > 0.8 && field_wealth > 0.7 && field_conflict < 0.3
then
emit_event PATTERN_EMERGED
drift fields.stability_index target 1.0 speed 0.05

rule detect_civilization_collapse_pattern
when
field_entropy > 0.8 && field_conflict > 0.7 && field_wealth < 0.2
then
emit_event PATTERN_EMERGED
drift fields.stability_index target 0.0 speed 0.1