# Heroic Reality Anchors DSL ⚓✨
# Phase 58: Định nghĩa các hiệu ứng nhận thức của Anh hùng lên thực tại.

rule heroic_resonance_boost
when
pressures.heroic_anchoring > 0.1
then
        # Khi có các điểm neo mạnh, sự thăng hoa dễ xảy ra hơn
pressure "apotheosis_pull" add 0.05
        # Giảm tốc độ sụp đổ văn minh
drift fields.entropy target 0.0 speed 0.01

rule reality_anchor_protection
when
pressures.heroic_anchoring > 0.2 && entropy > 0.8
then
        # Anh hùng ngăn chặn sự sụp đổ hàm sóng hoàn toàn
emit_event REALITY_STABILIZED
drift entropy target 0.5 speed 0.05