# Cosmic Reality Attractors DSL 🌌🌀
# Phase 52: Định nghĩa các trạng thái vĩ mô của thực tại WorldOS.

# 1. Eternal Stagnation (Đình trệ vĩnh cửu)
# -----------------------------------------
# Xảy ra khi Trật tự quá cao nhưng Sáng tạo bị triệt tiêu.

rule detect_eternal_stagnation
priority 200
when
field_order > 0.8 && innovation_rate < 0.2
then
drift topology.stagnation_pull target 1.0 speed 0.05
        
when
topology.stagnation_pull > 0.9
set active_attractor = "STAGNATION"
            # Trong trạng thái này, mọi thay đổi đều bị cản trở
drift axioms.innovation_impact target 0.1 speed 0.5
drift axioms.entropy_drift_base target 0.001 speed 0.5
emit_event REALITY_ANCHORED { type: "STAGNATION", description: "THỰC TẠI ĐÌNH TRỆ: Mọi cấu trúc trở nên cứng nhắc, sự tiến hóa bị đóng băng."

# 2. Apotheosis Vortex (Điểm kỳ dị thăng hoa)
# -------------------------------------------
# Xảy ra khi Tri thức và Cộng hưởng ý thức đạt ngưỡng tới hạn.

rule detect_apotheosis_vortex
priority 200
when
field_knowledge > 0.9 && field_resonance > 0.7
then
drift topology.apotheosis_pull target 1.0 speed 0.02
        
when
topology.apotheosis_pull > 0.95
set active_attractor = "APOTHEOSIS"
            # Thực tại bắt đầu "tan chảy", ranh giới giữa ý thức và vật chất mờ dần
drift axioms.reality_stiffness target 0.1 speed 0.1
drift axioms.causal_leak_rate target 0.5 speed 0.1
emit_event REALITY_BIFURCATION { type: "APOTHEOSIS", description: "ĐIỂM KỲ DỊ THĂNG HOA: Tri thức vượt ngưỡng, thực tại đang tự định nghĩa lại chính nó."

# 3. Heat Death / Dark Age (Nhiệt chết / Thời kỳ tối tăm)
# -------------------------------------------------------
# Xảy ra khi Entropy thống trị và Ý nghĩa sụp đổ.

rule detect_heat_death
priority 200
when
field_entropy > 0.9 && field_meaning < 0.1
then
drift topology.entropy_vortex_pull target 1.0 speed 0.1
        
when
topology.entropy_vortex_pull > 0.8
set active_attractor = "DARK_AGE"
            # Cấu trúc sụp đổ, thông tin bị mất mát
drift axioms.order_decay_rate target 0.5 speed 0.2
drift axioms.knowledge_retention target 0.2 speed 0.2
emit_event REALITY_COLLAPSE_WARNING { severity: "CRITICAL", description: "VÒNG XOÁY ENTROPY: Hệ thống đang trôi nhanh về phía hỗn loạn hoàn toàn."

# 4. Attractor Decay (Phân rã lực kéo khi không đủ điều kiện)
# -----------------------------------------------------------
rule attractor_gravity_decay
priority 10
then
        # Nếu không thỏa mãn các quy luật trên, lực kéo tự giảm dần
drift topology.stagnation_pull target 0.0 speed 0.01
drift topology.apotheosis_pull target 0.0 speed 0.01
drift topology.entropy_vortex_pull target 0.0 speed 0.01
        
when
topology.stagnation_pull < 0.1 && topology.apotheosis_pull < 0.1 && topology.entropy_vortex_pull < 0.1
set active_attractor = "NORMAL"