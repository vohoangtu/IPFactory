# WorldOS V6 - Causal Integrity & Observation Rules
# Manages Causal Debt (Karma) and Reality Saturation from Observation

# --- CAUSAL INTEGRITY (KARMA) ---

rule Causal_Correction_Threshold
priority 100
scope global
when
    # karma is provided by supreme entity state
abs_karma > 100.0
then
emit_event TRIGGER_CAUSAL_CORRECTION
    
    # Calculate magnitude: half of karma debt
set magnitude (abs_karma * 0.5)
    
    # New state values for the entity
set new_karma (karma * 0.1)
set karma_reduction_pct 0.9
    
metadata magnitude magnitude
metadata new_karma new_karma
metadata description "TÁI CÂN BẰNG TÍNH TOÀN VẸN: Nợ nhân quả vượt hằng số ổn định. Thực tại tự điều chỉnh."

rule Causal_Integrity_Evolution
priority 11
scope global
then
    # integrity decay = (abs_causal_debt * 0.01) + (entropy * 0.005)
set debt_abs causal_debt
when
debt_abs < 0.0
then
set debt_abs (0.0 - causal_debt)
      
set i_decay (debt_abs * 0.01)
set i_decay (i_decay + (entropy * 0.005))
    
set causal_integrity (causal_integrity - i_decay)
set causal_integrity (clamp causal_integrity 0.0 1.0)

# --- OBSERVATION INTERFERENCE (RECOHERENCE) ---

rule Observation_Decay
priority 50
scope global
when
is_observed == false
observation_load > 0.0
then
    # Natural recovery speed
set decay_amount 0.5
metadata decay_amount decay_amount
metadata description "Tự động phân rã áp lực quan sát theo thời gian (Entropy Recovery)."

rule Reality_Saturation_Warning
priority 10
scope global
when
observation_load > 10.0
then
emit_event REALITY_SATURATION_ALERT
metadata message "Cảnh báo Bão hòa Thực tại: Áp lực quan sát đang bẻ cong cấu trúc nhân quả."

rule Wavefunction_Collapse_Check
priority 5
scope global
when
is_observed == true
then
    # probability = observation_load * 0.02
set collapse_prob (observation_load * 0.02)
set collapse_prob (clamp collapse_prob 0.0 0.8)
    
when
random_chance < collapse_prob
then
emit_event WAVEFUNCTION_COLLAPSE