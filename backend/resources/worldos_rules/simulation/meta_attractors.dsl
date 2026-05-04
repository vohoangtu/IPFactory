# WorldOS V8: Meta-Attractor Graph DSL 🌌🌀🕸️
# Phối hợp giữa các Attractor Nodes và Transition Edges.

# -----------------------------------------------------------------------------
# 1. ATTRACTOR PULL RULES (Các nút trong Graph)
# -----------------------------------------------------------------------------

rule attractor_STAGNATION_pull
when
active_attractor == "STAGNATION"
then
drift fields.innovation target 0.1 speed 0.05
drift fields.stability_index target 1.0 speed 0.02
drift topology.stagnation_pull target 1.0 speed 0.1

rule attractor_RENAISSANCE_pull
when
active_attractor == "RENAISSANCE"
then
drift fields.knowledge target 0.8 speed 0.02
drift fields.innovation target 0.6 speed 0.03
drift topology.renaissance_pull target 1.0 speed 0.1

rule attractor_APOTHEOSIS_pull
when
active_attractor == "APOTHEOSIS"
then
drift fields.knowledge target 1.0 speed 0.05
drift fields.resonance target 0.9 speed 0.04
drift topology.apotheosis_pull target 1.0 speed 0.1

rule attractor_EMPIRE_pull
when
active_attractor == "EMPIRE"
then
drift fields.power target 0.9 speed 0.03
drift fields.authority target 0.9 speed 0.03
drift topology.empire_pull target 1.0 speed 0.1

# -----------------------------------------------------------------------------
# 2. TRANSITION EDGES (Các cạnh trong Graph)
# -----------------------------------------------------------------------------

rule transition_STAGNATION_to_RENAISSANCE
when
active_attractor == "STAGNATION" && field_innovation > 0.4 && field_knowledge > 0.5
then
set previous_attractor = "STAGNATION"
set active_attractor = "RENAISSANCE"
set attractor_stability = 0.5
emit_event ATTRACTOR_TRANSITION

rule transition_RENAISSANCE_to_APOTHEOSIS
when
active_attractor == "RENAISSANCE" && field_knowledge > 0.8 && field_innovation > 0.7
then
set previous_attractor = "RENAISSANCE"
set active_attractor = "APOTHEOSIS"
set attractor_stability = 0.35
emit_event ATTRACTOR_TRANSITION

rule transition_RENAISSANCE_to_EMPIRE
when
active_attractor == "RENAISSANCE" && field_power > 0.6
then
set previous_attractor = "RENAISSANCE"
set active_attractor = "EMPIRE"
set attractor_stability = 0.6
emit_event ATTRACTOR_TRANSITION

rule transition_EMPIRE_to_STAGNATION
when
active_attractor == "EMPIRE" && field_bureaucracy > 0.7
then
set previous_attractor = "EMPIRE"
set active_attractor = "STAGNATION"
set attractor_stability = 0.85
emit_event ATTRACTOR_TRANSITION

# -----------------------------------------------------------------------------
# 3. INITIALIZATION (Default State)
# -----------------------------------------------------------------------------

rule init_meta_attractor
when
active_attractor == "none" || active_attractor == ""
then
set active_attractor = "STAGNATION"
set attractor_stability = 0.85