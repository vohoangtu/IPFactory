# WorldOS V6 - Epochal Evolution Rules
# Manages the transition between grand historical Eras (Epochs)

rule Epoch_Transition_Check
priority 100
scope global
then
    # current metrics available:
    # relative_tick, entropy, stability, tech_level, population, social_order, resource_scarcity, innovation

    set should_transition false
    set next_era "unknown"

    # --- PROGRESSION PATHS ---

    # Stone Age -> Bronze Age
    if (tech_level > 0.2) then
    if (population > 1000) then
        set should_transition true
        set next_era "bronze_age"

    # Bronze Age -> Iron Age
    if (tech_level > 0.4) then
    if (social_order > 0.6) then
        set should_transition true
        set next_era "iron_age"

    # Transition to Enlightenment
    if (tech_level > 0.7) then
    if (innovation > 0.6) then
        set should_transition true
        set next_era "enlightenment"

    # --- CATASTROPHIC BRANCHES ---

    # Collapse into Chaos
    if (stability < 0.3) then
    if (entropy > 0.8) then
        set should_transition true
        set next_era "chaos"

rule Determine_Next_Epoch_Theme
priority 50
scope global
when
    should_transition == true
then
    emit_event INITIATE_EPOCH_TRANSITION
    
    # Mapping era keys to metadata
    if (next_era == "bronze_age") then
        metadata theme "bronze"
        metadata name "Kỷ Nguyên Đồng (The Bronze Age)"
        metadata description "Sự ra đời của luyện kim và các thành bang đầu tiên."
        metadata innovation_rate 1.2
        metadata complexity_growth 1.1

    if (next_era == "iron_age") then
        metadata theme "iron"
        metadata name "Kỷ Nguyên Sắt (The Iron Age)"
        metadata description "Kỹ thuật rèn sắt thay đổi bộ mặt chiến tranh và nông nghiệp."
        metadata innovation_rate 1.4
        metadata conflict_chance 0.4

    if (next_era == "enlightenment") then
        metadata theme "light"
        metadata name "Thời Đại Ánh Sáng (The Age of Enlightenment)"
        metadata description "Trí tuệ thăng hoa, các nền văn minh chạm tay vào những bí mật tối thượng."
        metadata innovation_rate 2.0
        metadata complexity_growth 1.3

    if (next_era == "chaos") then
        metadata theme "chaos"
        metadata name "Kỷ Nguyên Hỗn Loạn (The Age of Chaos)"
        metadata description "Thực tại rạn nứt, trật tự sụp đổ dưới sức nặng của sự hỗn mang."
        metadata entropy_rate 1.5
        metadata trauma_multiplier 1.2

    # Default fallback
    if (next_era == "unknown") then
        metadata theme "order"
        metadata name "Kỷ Nguyên Trật Tự (The Age of Order)"
        metadata description "Mọi thứ tiếp tục vận hành trong sự ổn định vĩnh cửu."