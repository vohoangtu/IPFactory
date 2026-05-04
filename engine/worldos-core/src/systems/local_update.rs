use std::collections::HashMap;
use crate::constants;
use crate::types::{UniverseState, WorldConfig, StructuredScar, MacroAgentType};
use crate::memory::ZoneActorIndex;

/// System for Phase 1: Local Zone Update.
/// Processes ecology, organization/decay, material pressure, cultural drift, and ruler influence.
pub fn run(state: &mut UniverseState, world: &WorldConfig, macro_idx: Option<&ZoneActorIndex>) {
    let genome = world.genome.clone().unwrap_or_default();
    
    // Phase 1: local zone update (entropy, organization, decay, ecology)
    let k1 = genome.entropy_coefficient * constants::K1_ENTROPY_PER_STRUCTURED;
    let eco_engine = crate::ecological_engine::EcologicalEngine::new(0.85);

    let tick = state.tick;
    let world_id = world.world_id;

    for (idx, z) in state.zones.iter_mut().enumerate() {
        // Ecological Update (§Phase 2)
        if let Some(event_desc) = eco_engine.update(&mut z.state) {
             state.scars.push(StructuredScar {
                tick,
                category: "ecological_event".to_string(),
                description: event_desc.clone(),
                actor_id: None,
                zone_id: Some(z.id),
                caused_by_id: None,
                metadata: serde_json::json!({
                    "event": event_desc
                }),
            });
        }

        let base = z.state.base_mass;
        let structured = z.state.structured_mass;
        let entropy = z.state.entropy;

        // Organization: some base_mass -> structured; entropy += k1 * delta_structured
        let extraction_rate = (0.01_f64).min((base - structured).max(0.0) / (base + 1e-6));
        let delta_structured = base * extraction_rate * 0.1;
        z.state.structured_mass += delta_structured;
        z.state.entropy += k1 * delta_structured;

        // Decay: structured loses to entropy
        z.state.structured_mass -= entropy * 0.02 * structured;
        z.state.structured_mass = z.state.structured_mass.max(0.0);

        // Drift nhẹ mỗi tick; có drift thì entropy không thể là 0 — sàn bằng đúng lượng drift
        z.state.entropy = (z.state.entropy + constants::ENTROPY_DRIFT_PER_TICK).min(1.0);
        z.state.entropy = z.state.entropy.max(constants::ENTROPY_DRIFT_PER_TICK);

        // Material Pressure Resolver (WorldOS V6 §8.3)
        // Resonance: >=2 materials same slug -> 1.5x effect; else 1.0
        let mat_count = z.state.active_materials.len();
        let mut material_stress_delta = 0.0;
        if mat_count > 0 {
            let count_by_slug: HashMap<String, u32> = z.state.active_materials.iter()
                .fold(HashMap::new(), |mut m, mat| {
                    *m.entry(mat.slug.clone()).or_insert(0) += 1;
                    m
                });
            for mat in &mut z.state.active_materials {
                let same_slug_count = *count_by_slug.get(&mat.slug).unwrap_or(&0);
                let resonance_mult = if same_slug_count >= 2 { 1.5 } else { 1.0 };
                let impact = mat.output * resonance_mult * 0.01;

                z.state.entropy += mat.pressure_coefficients.entropy * impact;
                if mat.pressure_coefficients.order > 0.0 {
                    z.state.entropy -= mat.pressure_coefficients.order * impact * 0.5;
                }
                z.state.embodied_knowledge += mat.pressure_coefficients.innovation * impact * 10.0;
                if mat.pressure_coefficients.growth > 0.0 {
                    z.state.free_energy += mat.pressure_coefficients.growth * impact * 5.0;
                }
                material_stress_delta += mat.pressure_coefficients.entropy * mat.output * resonance_mult * 0.02;

                if let Some(core) = &mut mat.recursive_core {
                    let precision = mat.output;
                    core.virtual_entropy = (core.virtual_entropy + 0.005 * (1.0 - precision)).min(1.0);
                    core.virtual_knowledge = (core.virtual_knowledge + 0.01 * precision).min(1.0);
                    if core.virtual_knowledge > 0.5 {
                        z.state.embodied_knowledge += core.virtual_knowledge * core.feedback_loop * 0.1;
                    }
                    if core.virtual_entropy > 0.9 {
                        z.state.entropy += core.virtual_entropy * core.feedback_loop * 0.05;
                        z.state.material_stress = (z.state.material_stress + 0.1).min(1.0);
                    }
                }
            }
        }

        z.state.enforce_invariant();

        let growth_rate = 0.001 * (z.state.tech_ceiling - z.state.knowledge_frontier).max(0.0);
        z.state.knowledge_frontier = (z.state.knowledge_frontier + growth_rate).min(z.state.tech_ceiling);

        z.state.update_material_stress();
        z.state.material_stress = (z.state.material_stress + material_stress_delta).clamp(0.0, 1.0);

        // Level 7: Civilization Field Genesis (M1 Migration)
        let s = &mut z.state;
        let structured_ratio = s.structured_mass / (s.base_mass + 1e-6);
        
        s.civ_fields.survival = (structured_ratio * 0.4 + (1.0 - s.entropy) * 0.6).clamp(0.0, 1.0);
        s.civ_fields.power = (structured_ratio * 0.7 + s.embodied_knowledge * 0.3).clamp(0.0, 1.0);
        s.civ_fields.wealth = (structured_ratio * 0.8 + s.free_energy / (s.base_mass * 2.0 + 1e-6)).clamp(0.0, 1.0);
        s.civ_fields.knowledge = (s.embodied_knowledge * 0.7 + s.knowledge_frontier * 0.3).clamp(0.0, 1.0);
        s.civ_fields.meaning = (s.cultural.myth_belief * 0.6 + (1.0 - s.material_stress) * 0.2 + s.entropy * 0.2).clamp(0.0, 1.0);
        s.civ_fields.clamp_mut();

        // Deep Sim Phase 3: Cultural drift (deterministic)
        let seed = world_id.wrapping_add(tick).wrapping_mul(31).wrapping_add(z.id as u64).wrapping_add(idx as u64);
        let h1 = (seed % 10_000) as f64 / 10_000.0;
        let h2 = (seed.wrapping_mul(17).wrapping_add(1) % 10_000) as f64 / 10_000.0;
        let drift1 = (h1 - 0.5) * 2.0 * constants::CULTURAL_DRIFT_MAGNITUDE;
        let drift2 = (h2 - 0.5) * 2.0 * constants::CULTURAL_DRIFT_MAGNITUDE;
        z.state.cultural.innovation_openness = (z.state.cultural.innovation_openness + drift1).clamp(0.0, 1.0);
        z.state.cultural.myth_belief = (z.state.cultural.myth_belief + drift2).clamp(0.0, 1.0);

        // Deep Sim Phase 3: Tech discovery proxy
        let discovery_roll = seed.wrapping_mul(13) % constants::TECH_DISCOVERY_MOD;
        let ok_stress = z.state.material_stress < 0.75;
        let ok_pop = z.state.population_proxy > 0.1;
        if discovery_roll == 0 && ok_stress && ok_pop {
            z.state.knowledge_frontier = (z.state.knowledge_frontier + constants::TECH_DISCOVERY_DELTA).min(z.state.tech_ceiling);
        }
    }

    // Deep Sim Phase 4: Ruler agents reduce entropy (order) in their zone.
    if let Some(idx) = macro_idx {
        for (i, z) in state.zones.iter_mut().enumerate() {
            for &ma_id in idx.actors_in_zone(i) {
                let ma = &state.macro_agents[ma_id as usize];
                if ma.agent_type == MacroAgentType::Ruler {
                    z.state.entropy = (z.state.entropy - 0.01 * ma.strength.clamp(0.0, 1.0)).max(0.0);
                }
            }
        }
    } else {
        for z in &mut state.zones {
            for ma in &state.macro_agents {
                if ma.zone_id == z.id && ma.agent_type == MacroAgentType::Ruler {
                    z.state.entropy = (z.state.entropy - 0.01 * ma.strength.clamp(0.0, 1.0)).max(0.0);
                }
            }
        }
    }
}
