/*
@rule_graph_id: market_dynamics_v2
@priority: 20
@trigger: ON_ECONOMY_TICK
*/

rule Golden_Age_Trade_Boom
scope: global
when:
historical_phase == "GOLDEN_AGE"
then:
drift market.prices.food by -0.1
drift civilization.economy.trade_volume by 20.0

rule Dark_Age_Inflation
scope: global
when:
historical_phase == "DARK__AGE"
then:
drift market.prices.food by 0.2
drift market.volatility by 0.1

rule Resonance_Market_Stability
scope: global
when:
resonance_field > 0.8
then:
drift market.volatility by -0.05
drift market.confidence by 0.1

rule High_Entropy_Price_Shock
scope: global
when:
entropy > 0.7
then:
drift market.prices.food by 0.5
emit EVENT_MARKET_SHOCK { severity: "high"

rule Knowledge_Driven_Abundance
scope: global
when:
fields.knowledge > 0.8
then:
    # Tri thức cao giúp tối ưu hóa sản xuất và giảm chi phí tài nguyên
drift market.prices.food by -0.15
drift civilization.economy.total_surplus by 50.0
emit ECONOMIC_INNOVATION { description: "Công nghệ mới đã làm giảm chi phí sinh tồn."

rule Social_Cohesion_Market_Trust
scope: global
when:
civilization.politics.social_cohesion > 0.7
then:
    # Sự gắn kết xã hội cao tạo niềm tin thị trường, giảm biến động
drift market.volatility by -0.1
drift civilization.economy.trade_flow by 10.0

rule Technocratic_Price_Control
scope: global
when:
civilization.politics.governance_type == "TECHNOCRACY"
then:
    # Kỹ trị giúp kiểm soát lạm phát và giữ giá ổn định
drift market.volatility by -0.05
drift market.prices.food target 1.0 speed 0.05