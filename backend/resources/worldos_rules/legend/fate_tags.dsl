# WorldOS V6 - Legend Formation Rules (V2)
# Refactored for Declarative Grammar and Mythic Weight

# --- 1. Archetype Potency (Drift based) ---

rule Legend_Potency_Drift
priority 50
scope agent
trigger traits.dominance
then
calc target_potency
formula "traits.dominance * traits.ambition"
drift legend_potency target target_potency speed 0.05

# --- 2. Discrete Fate Tags ---

rule The_Conqueror
priority 100
scope agent
category legend
trigger traits.dominance
when
traits.dominance > 0.95
traits.ambition > 0.95
then
emit_event FATE_TAG_CONQUEROR
set fate_tags.the_conqueror 1

rule The_Messiah
priority 100
scope agent
category legend
trigger traits.empathy
when
traits.empathy > 0.95
traits.hope > 0.95
then
emit_event FATE_TAG_MESSIAH
set fate_tags.the_messiah 1

rule The_Void_Seeker
priority 100
scope agent
category legend
trigger traits.curiosity
when
traits.curiosity > 0.95
then
emit_event FATE_TAG_VOID_SEEKER
set fate_tags.the_void_seeker 1

rule The_Avenger
priority 100
scope agent
category legend
trigger traits.vengeance
when
traits.vengeance > 0.95
then
emit_event FATE_TAG_AVENGER
set fate_tags.the_avenger 1

# --- 3. Simulation Self-Awareness ---

rule Awareness_of_the_Clock
priority 200
scope agent
category consciousness
trigger traits.pragmatism
when
traits.pragmatism > 0.95
traits.curiosity > 0.95
then
emit_event AWARENESS_OF_CLOCK
set fate_tags.awareness_of_the_clock 1

rule Simulation_Skepticism
priority 200
scope agent
category consciousness
trigger traits.pragmatism
when
traits.pragmatism > 0.95
traits.dogmatism < 0.1
then
emit_event SIMULATION_SKEPTICISM
set fate_tags.simulation_skepticism 1