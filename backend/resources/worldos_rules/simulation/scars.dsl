# Causal Scars Dynamics DSL 📜🩸
# Phase 51: Định nghĩa quy luật tiến hóa và phân rã của các vết sẹo thực tại.

# 1. Decay Rules (Quy luật phân rã theo thời gian)
# -----------------------------------------------

rule scar_memory_decay
priority 100
when
    count(scars) > 0
then
    drift scars.*.magnitude by -0.02
    # Logic: Giảm bớt cường độ của mọi vết sẹo (Historical Erosion)
    set scars.*.magnitude (scars.*.magnitude * 0.1)

# 2. Field Interference (Tác động lên các trường lực)
# ---------------------------------------------------

rule war_scar_interference
when
    has_scar('WAR_SCAR') == true
then
    # Chiến tranh để lại bóng ma sợ hãi và kìm hãm sáng tạo
    add fields.fear (scars.WAR_SCAR.magnitude * 0.5)
    add fields.innovation -(scars.WAR_SCAR.magnitude * 0.2)
    drift fields.entropy by (scars.WAR_SCAR.magnitude * 0.05)

rule plague_scar_interference
when
    has_scar('PLAGUE_SCAR') == true
then
    # Dịch bệnh để lại sự thận trọng và suy giảm dân số
    add fields.survival -(scars.PLAGUE_SCAR.magnitude * 0.3)
    drift fields.stability_index by -(scars.PLAGUE_SCAR.magnitude * 0.02)

rule innovation_scar_historical_momentum
when
    has_scar('INNOVATION_SCAR') == true
then
    # Các cột mốc sáng tạo lớn tạo ra đà tiến cho tương lai
    add fields.innovation (scars.INNOVATION_SCAR.magnitude * 0.3)
    add fields.meaning (scars.INNOVATION_SCAR.magnitude * 0.1)

rule causal_correction_reality_stiffness
when
    has_scar('CAUSAL_CORRECTION_SCAR') == true
then
    # Sự can thiệp nhân quả mạnh tay làm thực tại trở nên "cứng nhắc"
    drift fields.stability_index by (scars.CAUSAL_CORRECTION_SCAR.magnitude * 0.1)
    add fields.chaos -(scars.CAUSAL_CORRECTION_SCAR.magnitude * 0.5)