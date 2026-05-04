# WorldOS Rule Engine — civilization rules (DSL v2)
# State contract: docs/WorldOS_DSL_Spec.md
# Cú pháp: rule, priority, cooldown, scope, when (expr op expr; dòng "or" = OR), chance (expr), then actions.

rule revolution_trigger
  priority 70
  cooldown 10y
  scope civilization
  when
    civilization.politics.legitimacy_aggregate < 0.3
    civilization.politics.elite_overproduction > 0.6
  chance 0.15
  then
    emit_event REVOLUTION

rule revolt_high_entropy
  priority 80
  cooldown 20y
  scope civilization
  when
    civilization.politics.legitimacy_aggregate < 0.3
    entropy > 0.6
  chance sigmoid(entropy)
  then
    emit_event REVOLUTION
    add civilization.politics.social_unrest 0.4
    spawn_actor revolutionary_leader

rule chaos_high
  when
    entropy > 0.85
    stability_index < 0.3
  then
    emit_event CHAOS_SPIKE
    adjust_stability -0.1

rule entropy_critical
  priority 90
  when
    entropy > 0.92
  then
    emit_event CRISIS
    adjust_entropy -0.02

# (A OR B) AND C: bất ổn khi legitimacy thấp HOẶC corruption cao, và entropy đủ cao
rule unrest_legitimacy_or_corruption
  priority 60
  scope civilization
  when
    civilization.politics.legitimacy_aggregate < 0.25
  or
    civilization.politics.corruption > 0.75
  chance 0.3
  then
    emit_event UNREST
    add civilization.politics.social_unrest 0.2
