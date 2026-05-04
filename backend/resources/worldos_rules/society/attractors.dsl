# WorldOS V6 - Societal Attractors (V2)
# Defines the "Pull" towards specific societal archetypes based on field density.

rule Attractor_Pull_Theocracy
priority 50
scope global
category societal_attractors
trigger field_belief
when
field_belief > 0.7
then
calc pull_strength
formula "(field_belief * 0.8 + field_authority * 0.2)"
drift attractor_theocracy target pull_strength speed 0.05
drift field_ideology target 0.9 speed 0.01

rule Attractor_Pull_Technocracy
priority 51
scope global
category societal_attractors
trigger field_knowledge
when
field_knowledge > 0.7
then
calc pull_strength
formula "(field_knowledge * 0.8 + field_ideology * 0.2)"
drift attractor_technocracy target pull_strength speed 0.05
drift field_order target 0.8 speed 0.01

rule Attractor_Pull_Feudalism
priority 52
scope global
category societal_attractors
trigger field_power
when
field_power > 0.6
then
calc pull_strength
formula "(field_power * 0.7 + field_fear * 0.3)"
drift attractor_feudalism target pull_strength speed 0.05
drift field_authority target 0.95 speed 0.02