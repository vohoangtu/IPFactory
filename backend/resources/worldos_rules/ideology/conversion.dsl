# WorldOS V6 - Ideology Conversion Rules
# Chuyển đổi từ IdeologyConversionService.php

rule Calculate_Ideology_Conversion_Rate
priority 50
scope civilization
when
true
then
    # baseRate = 0.01
    # legitimacyFactor = 0.5 + 0.5 * legitimacy
    # coherenceFactor = 0.5 + 0.5 * coherence
    # rate = baseRate * legitimacyFactor * coherenceFactor * distanceFactor
    
set civilization.ideology.temp_legitimacy (civilization.politics.legitimacy_aggregate * 0.5)
add civilization.ideology.temp_legitimacy 0.5
    
set civilization.ideology.temp_coherence (civilization.cultural_coherence * 0.5)
add civilization.ideology.temp_coherence 0.5
    
set civilization.ideology.conversion_rate 0.01
set civilization.ideology.conversion_rate (civilization.ideology.conversion_rate * civilization.ideology.temp_legitimacy)
set civilization.ideology.conversion_rate (civilization.ideology.conversion_rate * civilization.ideology.temp_coherence)
    
    # distanceFactor được tính riêng cho từng cặp Ideology trong PHP hoặc qua DSL phức tạp hơn
clamp civilization.ideology.conversion_rate 0.0 0.1