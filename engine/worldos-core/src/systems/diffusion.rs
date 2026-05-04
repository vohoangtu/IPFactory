use std::collections::HashMap;
use crate::constants;
use crate::types::{UniverseState, WorldConfig, CulturalVector, CivilizationFields, CascadePhase, Fix};
use crate::memory::ZoneActorIndex;

/// System for Phase 3: Diffusion and Flow.
/// Processes entropy, tech, culture, population, and trade flow between zones (including ghost zones).
pub fn run(state: &mut UniverseState, world: &WorldConfig, _macro_idx: Option<&ZoneActorIndex>) {
    let genome = world.genome.clone().unwrap_or_default();
    let beta = genome.diffusion_rate;
    let n_len_total = state.zones.len();
    if n_len_total == 0 { return; }

    let mut entropy_deltas = vec![0.0; n_len_total];
    let mut tech_deltas = vec![0.0; n_len_total];
    let mut culture_deltas = vec![CulturalVector::default(); n_len_total];
    let mut civ_field_deltas = vec![CivilizationFields::default(); n_len_total];
    let mut population_deltas = vec![0.0_f64; n_len_total];
    let mut trade_deltas = vec![0.0_f64; n_len_total];

    // Effective wealth per zone for trade flow
    let effective_wealth: Vec<f64> = state.zones.iter()
        .map(|z| {
            let base = if z.state.resource_capacity > 1e-9 {
                (z.state.resource_capacity * 1.5 + 0.5).min(1.0)
            } else {
                ((z.state.base_mass * 0.01 + 1.0) * (1.0 - z.state.material_stress * 0.5) + z.state.free_energy * 0.001).min(1.0)
            };
            if z.state.wealth_proxy > 1e-9 {
                z.state.wealth_proxy
            } else {
                base
            }
        })
        .collect();

    // Population pressure per zone
    let population_pressures: Vec<f64> = state.zones.iter()
        .map(|z| {
            let resources = if z.state.resource_capacity > 1e-9 {
                (z.state.resource_capacity * 1.5 + 0.5).max(0.1)
            } else {
                (z.state.base_mass * 0.01 + 1.0) * (1.0 - z.state.material_stress * 0.5) + z.state.free_energy * 0.001
            };
            (z.state.population_proxy + 1e-6) / (resources + 1e-6)
        })
        .collect();

    let zone_map: HashMap<u32, usize> = state.zones.iter().enumerate().map(|(idx, z)| (z.id, idx)).collect();
    let ghost_map: HashMap<u32, usize> = state.ghost_zones.iter().enumerate().map(|(idx, gz)| (gz.id, idx)).collect();

    for i in 0..n_len_total {
        let zone = &state.zones[i];
        let n_neigh = zone.neighbors.len() as f64;
        if n_neigh < 1e-9 { continue; }
        
        let phase_factor = phase_diffusion_factor(zone.state.cascade_phase);

        let mut s_diff_sum = 0.0;
        let mut t_diff_sum = 0.0;
        let mut c_diff_sum = CulturalVector::default();
        let mut civ_diff_sum = CivilizationFields::default();

        for &id in &zone.neighbors {
            if let Some(&j) = zone_map.get(&id) {
                let neighbor = &state.zones[j];
                s_diff_sum += neighbor.state.entropy - zone.state.entropy;
                t_diff_sum += neighbor.state.knowledge_frontier - zone.state.knowledge_frontier;
                
                c_diff_sum.tradition_rigidity += neighbor.state.cultural.tradition_rigidity - zone.state.cultural.tradition_rigidity;
                c_diff_sum.innovation_openness += neighbor.state.cultural.innovation_openness - zone.state.cultural.innovation_openness;
                c_diff_sum.collective_trust += neighbor.state.cultural.collective_trust - zone.state.cultural.collective_trust;
                c_diff_sum.violence_tolerance += neighbor.state.cultural.violence_tolerance - zone.state.cultural.violence_tolerance;
                c_diff_sum.institutional_respect += neighbor.state.cultural.institutional_respect - zone.state.cultural.institutional_respect;
                c_diff_sum.myth_belief += neighbor.state.cultural.myth_belief - zone.state.cultural.myth_belief;

                civ_diff_sum.survival += neighbor.state.civ_fields.survival - zone.state.civ_fields.survival;
                civ_diff_sum.reproduction += neighbor.state.civ_fields.reproduction - zone.state.civ_fields.reproduction;
                civ_diff_sum.wealth += neighbor.state.civ_fields.wealth - zone.state.civ_fields.wealth;
                civ_diff_sum.power += neighbor.state.civ_fields.power - zone.state.civ_fields.power;
                civ_diff_sum.knowledge += neighbor.state.civ_fields.knowledge - zone.state.civ_fields.knowledge;
                civ_diff_sum.meaning += neighbor.state.civ_fields.meaning - zone.state.civ_fields.meaning;
                civ_diff_sum.status += neighbor.state.civ_fields.status - zone.state.civ_fields.status;
                civ_diff_sum.belonging += neighbor.state.civ_fields.belonging - zone.state.civ_fields.belonging;
            } else if let Some(&k) = ghost_map.get(&id) {
                let ghost = &state.ghost_zones[k].state_snapshot.state;
                s_diff_sum += ghost.entropy - zone.state.entropy;
                t_diff_sum += ghost.knowledge_frontier - zone.state.knowledge_frontier;
                
                c_diff_sum.tradition_rigidity += ghost.cultural.tradition_rigidity - zone.state.cultural.tradition_rigidity;
                c_diff_sum.innovation_openness += ghost.cultural.innovation_openness - zone.state.cultural.innovation_openness;
                c_diff_sum.collective_trust += ghost.cultural.collective_trust - zone.state.cultural.collective_trust;
                c_diff_sum.violence_tolerance += ghost.cultural.violence_tolerance - zone.state.cultural.violence_tolerance;
                c_diff_sum.institutional_respect += ghost.cultural.institutional_respect - zone.state.cultural.institutional_respect;
                c_diff_sum.myth_belief += ghost.cultural.myth_belief - zone.state.cultural.myth_belief;

                civ_diff_sum.survival += ghost.civ_fields.survival - zone.state.civ_fields.survival;
                civ_diff_sum.reproduction += ghost.civ_fields.reproduction - zone.state.civ_fields.reproduction;
                civ_diff_sum.wealth += ghost.civ_fields.wealth - zone.state.civ_fields.wealth;
                civ_diff_sum.power += ghost.civ_fields.power - zone.state.civ_fields.power;
                civ_diff_sum.knowledge += ghost.civ_fields.knowledge - zone.state.civ_fields.knowledge;
                civ_diff_sum.meaning += ghost.civ_fields.meaning - zone.state.civ_fields.meaning;
                civ_diff_sum.status += ghost.civ_fields.status - zone.state.civ_fields.status;
                civ_diff_sum.belonging += ghost.civ_fields.belonging - zone.state.civ_fields.belonging;
            }
        }
        
        let beta_zone = beta * phase_factor;
        entropy_deltas[i] = beta_zone * s_diff_sum / n_neigh;
        tech_deltas[i] = beta_zone * 0.5 * t_diff_sum / n_neigh;
        
        culture_deltas[i].tradition_rigidity = beta_zone * c_diff_sum.tradition_rigidity / n_neigh;
        culture_deltas[i].innovation_openness = beta_zone * c_diff_sum.innovation_openness / n_neigh;
        culture_deltas[i].collective_trust = beta_zone * c_diff_sum.collective_trust / n_neigh;
        culture_deltas[i].violence_tolerance = beta_zone * c_diff_sum.violence_tolerance / n_neigh;
        culture_deltas[i].institutional_respect = beta_zone * c_diff_sum.institutional_respect / n_neigh;
        culture_deltas[i].myth_belief = beta_zone * c_diff_sum.myth_belief / n_neigh;

        civ_field_deltas[i].survival = beta_zone * civ_diff_sum.survival / n_neigh;
        civ_field_deltas[i].reproduction = beta_zone * civ_diff_sum.reproduction / n_neigh;
        civ_field_deltas[i].wealth = beta_zone * civ_diff_sum.wealth / n_neigh;
        civ_field_deltas[i].power = beta_zone * civ_diff_sum.power / n_neigh;
        civ_field_deltas[i].knowledge = beta_zone * civ_diff_sum.knowledge / n_neigh;
        civ_field_deltas[i].meaning = beta_zone * civ_diff_sum.meaning / n_neigh;
        civ_field_deltas[i].status = beta_zone * civ_diff_sum.status / n_neigh;
        civ_field_deltas[i].belonging = beta_zone * civ_diff_sum.belonging / n_neigh;

        // Flow deltas
        let pi = population_pressures[i];
        let wi = effective_wealth[i];

        for &id in &zone.neighbors {
            let (pj, wj) = if let Some(&j) = zone_map.get(&id) {
                (population_pressures[j], effective_wealth[j])
            } else if let Some(&k) = ghost_map.get(&id) {
                let ghost = &state.ghost_zones[k].state_snapshot.state;
                let resources = if ghost.resource_capacity > 1e-9 {
                    (ghost.resource_capacity * 1.5 + 0.5).max(0.1)
                } else {
                    (ghost.base_mass * 0.01 + 1.0) * (1.0 - ghost.material_stress * 0.5) + ghost.free_energy * 0.001
                };
                let g_pj = (ghost.population_proxy + 1e-6) / (resources + 1e-6);
                let g_wj = if ghost.wealth_proxy > 1e-9 { ghost.wealth_proxy } else { 0.5 };
                (g_pj, g_wj)
            } else {
                continue;
            };

            // Population flow
            if pi > pj {
                let flow = (constants::POPULATION_FLOW_COEFFICIENT * (pi - pj) / n_neigh)
                    .min(constants::MAX_POPULATION_FLOW_PER_TICK);
                population_deltas[i] -= flow;
                if let Some(&j) = zone_map.get(&id) {
                    population_deltas[j] += flow;
                }
            }

            // Trade flow
            if (zone.id as u64) < (id as u64) {
                let flow = (constants::TRADE_FLOW_COEFFICIENT * (wi - wj) / n_neigh)
                    .max(-constants::MAX_TRADE_FLOW_PER_TICK)
                    .min(constants::MAX_TRADE_FLOW_PER_TICK);
                trade_deltas[i] -= flow;
                if let Some(&j) = zone_map.get(&id) {
                    trade_deltas[j] += flow;
                }
            }
        }
    }

    // Apply all deltas
    for i in 0..n_len_total {
        let z = &mut state.zones[i];
        z.state.entropy = (z.state.entropy + entropy_deltas[i]).clamp(0.0, 1.0);
        z.state.knowledge_frontier = (z.state.knowledge_frontier + tech_deltas[i]).clamp(0.0, 1.0);
        
        z.state.cultural.tradition_rigidity = (z.state.cultural.tradition_rigidity + culture_deltas[i].tradition_rigidity).clamp(0.0, 1.0);
        z.state.cultural.innovation_openness = (z.state.cultural.innovation_openness + culture_deltas[i].innovation_openness).clamp(0.0, 1.0);
        z.state.cultural.collective_trust = (z.state.cultural.collective_trust + culture_deltas[i].collective_trust).clamp(0.0, 1.0);
        z.state.cultural.violence_tolerance = (z.state.cultural.violence_tolerance + culture_deltas[i].violence_tolerance).clamp(0.0, 1.0);
        z.state.cultural.institutional_respect = (z.state.cultural.institutional_respect + culture_deltas[i].institutional_respect).clamp(0.0, 1.0);
        z.state.cultural.myth_belief = (z.state.cultural.myth_belief + culture_deltas[i].myth_belief).clamp(0.0, 1.0);

        // Apply decay
        z.state.civ_fields.survival *= constants::FIELD_PRESERVATION_RATE;
        z.state.civ_fields.reproduction *= constants::FIELD_PRESERVATION_RATE;
        z.state.civ_fields.wealth *= constants::FIELD_PRESERVATION_RATE;
        z.state.civ_fields.power *= constants::FIELD_PRESERVATION_RATE;
        z.state.civ_fields.knowledge *= constants::FIELD_PRESERVATION_RATE;
        z.state.civ_fields.meaning *= constants::FIELD_PRESERVATION_RATE;
        z.state.civ_fields.status *= constants::FIELD_PRESERVATION_RATE;
        z.state.civ_fields.belonging *= constants::FIELD_PRESERVATION_RATE;

        z.state.civ_fields.survival = (z.state.civ_fields.survival + civ_field_deltas[i].survival).clamp(0.0, 1.0);
        z.state.civ_fields.reproduction = (z.state.civ_fields.reproduction + civ_field_deltas[i].reproduction).clamp(0.0, 1.0);
        z.state.civ_fields.wealth = (z.state.civ_fields.wealth + civ_field_deltas[i].wealth).clamp(0.0, 1.0);
        z.state.civ_fields.power = (z.state.civ_fields.power + civ_field_deltas[i].power).clamp(0.0, 1.0);
        z.state.civ_fields.knowledge = (z.state.civ_fields.knowledge + civ_field_deltas[i].knowledge).clamp(0.0, 1.0);
        z.state.civ_fields.meaning = (z.state.civ_fields.meaning + civ_field_deltas[i].meaning).clamp(0.0, 1.0);
        z.state.civ_fields.status = (z.state.civ_fields.status + civ_field_deltas[i].status).clamp(0.0, 1.0);
        z.state.civ_fields.belonging = (z.state.civ_fields.belonging + civ_field_deltas[i].belonging).clamp(0.0, 1.0);

        z.state.population_proxy = (z.state.population_proxy + population_deltas[i]).clamp(0.0, 1.0);
        if z.state.wealth_proxy < 1e-9 {
            z.state.wealth_proxy = effective_wealth[i];
        }
        z.state.wealth_proxy = (z.state.wealth_proxy + trade_deltas[i]).clamp(0.0, 1.0);
    }
}

fn phase_diffusion_factor(phase: CascadePhase) -> Fix {
    match phase {
        CascadePhase::Normal => constants::PHASE_DIFFUSION_NORMAL,
        CascadePhase::Famine => constants::PHASE_DIFFUSION_FAMINE,
        CascadePhase::Riots => constants::PHASE_DIFFUSION_RIOTS,
        CascadePhase::Collapse => constants::PHASE_DIFFUSION_COLLAPSE,
    }
}
