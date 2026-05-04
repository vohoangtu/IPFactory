# WorldOS V6 - Archetype Evolution Rules
# Chuyển đổi từ TraitMapper::detectArchetypeShift

rule Commoner_to_Opportunist
priority 50
scope agent
when
current_archetype == "Commoner"
traits.ambition > 0.8
chance 1.0
then
emit_event ARCHETYPE_SHIFTED
set current_archetype "Opportunist"

rule Commoner_to_Sage
priority 50
scope agent
when
current_archetype == "Commoner"
traits.empathy > 0.8
chance 1.0
then
emit_event ARCHETYPE_SHIFTED
set current_archetype "Sage"

rule Opportunist_to_Warlord
priority 60
scope agent
when
current_archetype == "Opportunist"
traits.coercion > 0.8
chance 1.0
then
emit_event ARCHETYPE_SHIFTED
set current_archetype "Warlord"

rule Sage_to_High_Priest
priority 60
scope agent
when
current_archetype == "Sage"
traits.dogmatism > 0.8
chance 1.0
then
emit_event ARCHETYPE_SHIFTED
set current_archetype "High_Priest"

rule Sage_to_Scholar
priority 60
scope agent
when
current_archetype == "Sage"
traits.curiosity > 0.9
chance 1.0
then
emit_event ARCHETYPE_SHIFTED
set current_archetype "Scholar"

rule Opportunist_to_Merchant_Lord
priority 60
scope agent
when
current_archetype == "Opportunist"
traits.pragmatism > 0.9
chance 1.0
then
emit_event ARCHETYPE_SHIFTED
set current_archetype "Merchant_Lord"

rule Any_to_Zealot
priority 90
scope agent
when
traits.dogmatism > 0.9
chance 1.0
then
emit_event ARCHETYPE_SHIFTED
set current_archetype "Zealot"