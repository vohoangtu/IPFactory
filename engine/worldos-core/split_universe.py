import os

source_file = "c:/projects/IPFactory/engine/worldos-core/src/universe.rs"
with open(source_file, "r", encoding="utf-8") as f:
    lines = f.readlines()

def get_lines(start, end):
    return "".join(lines[start-1:end])

# Meta
meta_code = """use crate::types::*;
use crate::constants;

impl UniverseState {
"""
meta_code += get_lines(45, 78) # run_archetype_discovery
meta_code += get_lines(80, 89) # get_standard_archetypes
meta_code += get_lines(799, 822) # apply_dark_attractors
meta_code += get_lines(824, 842) # apply_intelligence_explosion
meta_code += get_lines(844, 876) # check_phase_transition
meta_code += get_lines(878, 900) # explore_futures
meta_code += get_lines(943, 959) # check_meta_cycle
meta_code += "}\n\n#[cfg(test)]\nmod tests {\n    use super::*;\n    use crate::types::{WorldConfig, CivilizationPhase, CivilizationAttractor, DarkAttractor};\n"
meta_code += get_lines(1263, 1278) # test_explore_futures
meta_code += get_lines(1280, 1315) # test_level8_engines_boundedness
meta_code += "}\n"
with open("c:/projects/IPFactory/engine/worldos-core/src/universe_meta.rs", "w", encoding="utf-8") as f: f.write(meta_code)


# Narrative
narrative_code = """use crate::types::*;

impl UniverseState {
"""
narrative_code += get_lines(169, 209) # apply_narrative_influence
narrative_code += get_lines(1007, 1035) # perform_deity_intervention
narrative_code += "}\n\n#[cfg(test)]\nmod tests {\n    use super::*;\n    use crate::types::WorldConfig;\n"
narrative_code += get_lines(1237, 1261) # test_deity_intervention_boundedness
narrative_code += get_lines(1369, 1426) # test_intelligence_and_narrative
narrative_code += "}\n"
with open("c:/projects/IPFactory/engine/worldos-core/src/universe_narrative.rs", "w", encoding="utf-8") as f: f.write(narrative_code)


# Social
social_code = """use crate::types::*;
use crate::constants;

impl UniverseState {
"""
social_code += get_lines(643, 680) # tick_vocation_drift
social_code += get_lines(720, 736) # trigger_micro_mode
social_code += get_lines(738, 758) # resolve_micro_mode
social_code += get_lines(760, 781) # pressure_at_zone
social_code += get_lines(783, 797) # apply_attractor_fields
social_code += "}\n\n#[cfg(test)]\nmod tests {\n    use super::*;\n"
social_code += get_lines(1042, 1052) # test_micro_mode_trigger
social_code += "}\n"
with open("c:/projects/IPFactory/engine/worldos-core/src/universe_social.rs", "w", encoding="utf-8") as f: f.write(social_code)


# Quantum
quantum_code = """use crate::types::*;

impl UniverseState {
"""
quantum_code += get_lines(682, 702) # tick_quantum_overlays
quantum_code += get_lines(704, 718) # observe_zone
quantum_code += "}\n"
with open("c:/projects/IPFactory/engine/worldos-core/src/universe_quantum.rs", "w", encoding="utf-8") as f: f.write(quantum_code)

# Remove lines from universe.rs (by keeping lines that are NOT in the ranges above)
ranges_to_remove = [
    (45, 78), (80, 89), (799, 822), (824, 842), (844, 876), (878, 900), (943, 959),
    (1263, 1278), (1280, 1315),
    (169, 209), (1007, 1035), (1237, 1261), (1369, 1426),
    (643, 680), (720, 736), (738, 758), (760, 781), (783, 797), (1042, 1052),
    (682, 702), (704, 718)
]

filtered_lines = []
for idx, line in enumerate(lines):
    line_num = idx + 1
    remove = False
    for start, end in ranges_to_remove:
        if start <= line_num <= end:
            remove = True
            break
    if not remove:
        filtered_lines.append(line)

with open("c:/projects/IPFactory/engine/worldos-core/src/universe.rs", "w", encoding="utf-8") as f:
    f.writelines(filtered_lines)

print("Done generating 4 module files and compacting universe.rs")
