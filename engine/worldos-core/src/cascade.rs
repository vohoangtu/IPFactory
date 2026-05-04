//! Event-driven cascade: when Pressure > COLLAPSE_THRESHOLD, emit event and cascade Famine → Riots → Collapse.

use crate::constants;
use crate::types::{CascadePhase, WorldConfig, UniverseState};
use serde::{Serialize, Deserialize};

fn phase_name(p: CascadePhase) -> &'static str {
    match p {
        CascadePhase::Normal => "Normal",
        CascadePhase::Famine => "Famine",
        CascadePhase::Riots => "Riots",
        CascadePhase::Collapse => "Collapse",
    }
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub enum SimEvent {
    Crisis,
    Famine,
    Riots,
    Collapse,
    RegimeShift,
    MicroMode,
    MetaCycle,
    DeityIntervention,
    WavefunctionCollapse,
    Cosmogenesis, // Phase 61: Spontaneous generation of a child universe (§V9)
    // Doc 21 §8: Black Swan / Rare Event (deterministic hash(seed, tick))
    BlackSwanMeteor,
    BlackSwanPlague,
    BlackSwanProphet,
}

/// Process one tick: run 3-phase tick, then check pressure and optionally cascade.
/// Returns events emitted this tick (for logging / Laravel).
pub fn tick_with_cascade(
    state: &mut UniverseState,
    world: &WorldConfig,
    max_cascade: usize,
    macro_idx: Option<&crate::memory::ZoneActorIndex>,
) -> Vec<SimEvent> {
    state.tick(world, macro_idx);
    let mut events = Vec::new();

    for i in 0..state.zones.len() {
        let p = state.pressure_at_zone(i, macro_idx);
        let phase = state.zones[i].state.cascade_phase;

        // Doc 21 §10: Hazard model — P(phase change) = sigmoid(pressure); deterministic RNG(seed, tick, zone_id).
        let p_f64: f64 = p;
        let threshold_f64: f64 = constants::COLLAPSE_THRESHOLD;
        let steepness_f64: f64 = constants::HAZARD_SIGMOID_STEEPNESS;
 
        let p_transition = 1.0
            / (1.0
                + (-steepness_f64 * (p_f64 - threshold_f64)).exp());
        
        let seed_u = match &world.world_seed {
            Some(serde_json::Value::Number(n)) => n.as_u64().unwrap_or(0),
            _ => world.world_id.wrapping_add(state.universe_id),
        };
        let roll = ((seed_u
            .wrapping_add(state.tick)
            .wrapping_mul(31)
            .wrapping_add(i as u64)
            .wrapping_mul(0x9e3779b97f4a7c15)
            % 10000) as f64)
            / 10000.0;

        if p >= constants::COLLAPSE_THRESHOLD && roll < p_transition {
            // Advance cascade: Normal → Famine → Riots → Collapse (probabilistic)
            let (next_phase, event) = match phase {
                CascadePhase::Normal => (CascadePhase::Famine, SimEvent::Famine),
                CascadePhase::Famine => (CascadePhase::Riots, SimEvent::Riots),
                CascadePhase::Riots => (CascadePhase::Collapse, SimEvent::Collapse),
                CascadePhase::Collapse => (CascadePhase::Collapse, SimEvent::Crisis), // hold Collapse, still emit Crisis
            };
            state.zones[i].state.cascade_phase = next_phase;
            events.push(event.clone());
            if phase == CascadePhase::Normal {
                events.push(SimEvent::Crisis);
            }
            // Trigger Micro Mode if pressure is extremely high (§3.2)
            if p > 0.8 {
                events.push(SimEvent::MicroMode);
                state.trigger_micro_mode(i);
                state.scars.push(crate::types::StructuredScar {
                    tick: state.tick,
                    category: "system_state".to_string(),
                    description: format!("Micro-Mode Triggered in Zone {}", i),
                    actor_id: None,
                    zone_id: Some(i as u32),
                    caused_by_id: None,
                    metadata: serde_json::json!({
                        "pressure": p,
                        "mode": "micro"
                    }),
                });
            }
            // Escalating entropy/trauma: Famine light, Riots stronger, Collapse heaviest
            let (entropy_step, trauma_step) = match next_phase {
                CascadePhase::Famine => (0.03, 0.02),
                CascadePhase::Riots => (0.05, 0.04),
                CascadePhase::Collapse => (0.08, 0.06),
                CascadePhase::Normal => (0.05, 0.03),
            };
            state.zones[i].state.entropy = (state.zones[i].state.entropy + entropy_step).min(1.0);
            state.zones[i].state.trauma = (state.zones[i].state.trauma + trauma_step).min(1.0);
            state.zones[i].state.update_material_stress();
            state.scars.push(crate::types::StructuredScar {
                tick: state.tick,
                category: "cascade_event".to_string(),
                description: format!("Transition to Phase: {}", phase_name(next_phase)),
                actor_id: None,
                zone_id: Some(i as u32),
                caused_by_id: None,
                metadata: serde_json::json!({
                    "phase": phase_name(next_phase),
                    "entropy": state.zones[i].state.entropy,
                    "trauma": state.zones[i].state.trauma,
                    "pressure": p
                }),
            });
            if next_phase == CascadePhase::Collapse {
                state.zones[i].state.active_materials.clear();
                // Doc 21 §4b.II: Reset dynamics — reduce pressure components to avoid runaway collapse.
                state.zones[i].state.entropy = (state.zones[i].state.entropy - 0.1).max(0.0);
                state.zones[i].state.trauma = (state.zones[i].state.trauma - 0.05).max(0.0);
                state.zones[i].state.inequality = (state.zones[i].state.inequality - 0.05).max(0.0);
                state.zones[i].state.update_material_stress();
            }
            // Doc 21 §10: Event cascade — inject pressure into neighbors (no phase change; structural cascade still decides phase).
            let zone_map: std::collections::HashMap<u32, usize> = state.zones.iter()
                .enumerate()
                .map(|(i, z)| (z.id, i))
                .collect();
            let neighbor_ids: Vec<u32> = state.zones[i].neighbors.clone();
            for neighbor_id in neighbor_ids {
                if let Some(&k) = zone_map.get(&neighbor_id) {
                    state.zones[k].state.entropy =
                        (state.zones[k].state.entropy + constants::EVENT_CASCADE_ENTROPY_NEIGHBOR).min(1.0);
                    state.zones[k].state.trauma =
                        (state.zones[k].state.trauma + constants::EVENT_CASCADE_TRAUMA_NEIGHBOR).min(1.0);
                    state.zones[k].state.inequality =
                        (state.zones[k].state.inequality + constants::EVENT_CASCADE_INEQUALITY_NEIGHBOR).min(1.0);
                    state.zones[k].state.update_material_stress();
                }
            }
        } else if p < constants::COLLAPSE_THRESHOLD {
            state.zones[i].state.cascade_phase = CascadePhase::Normal;
            if !state.zones[i].state.agents.is_empty() && p < 0.4 {
                state.resolve_micro_mode(i);
            }
        }

        // Phase 53: Hyper-Agents & Deities (§53)
        let mut deity_interventions = Vec::new();
        for agent in &state.zones[i].state.agents {
            for (idx, &val) in agent.trait_vector.iter().enumerate() {
                if val > 0.95 {
                    deity_interventions.push((agent.id, idx));
                }
            }
        }

        for (agent_id, trait_idx) in deity_interventions {
            events.push(SimEvent::DeityIntervention);
            state.perform_deity_intervention(i, trait_idx);
            state.scars.push(crate::types::StructuredScar {
                tick: state.tick,
                category: "divine_intervention".to_string(),
                description: format!("Deity Intervention triggered by Trait {}", trait_idx),
                actor_id: Some(agent_id),
                zone_id: Some(i as u32),
                caused_by_id: None,
                metadata: serde_json::json!({
                    "trait_index": trait_idx,
                    "impact": "high"
                }),
            });
        }

        // Phase 57: Quantum Realities & Observer Effect (§57)
        let mut collapse_triggered = false;
        let mut entropy_delta = 0.0;
        
        // 1. Immutable check (or scoped mutable) to get changes
        if let Some(overlay) = &state.zones[i].state.quantum_overlay {
            let presence = overlay.observer_presence;
            if presence > 0.1 {
                entropy_delta = 0.01 * presence;
            }
        }

        // 2. Apply entropy change (needs mutable state.zones[i].state)
        if entropy_delta > 0.0 {
            state.zones[i].state.entropy = (state.zones[i].state.entropy + entropy_delta).min(1.0);
        }

        // 3. Update overlay state (needs mutable state.zones[i].state.quantum_overlay)
        if let Some(overlay) = &mut state.zones[i].state.quantum_overlay {
            if overlay.observer_presence > 0.1 {
                if overlay.superposition_depth > 0.5 {
                    overlay.superposition_depth = (overlay.superposition_depth - overlay.probability_decay).max(0.0);
                    if overlay.superposition_depth <= 0.0 {
                        collapse_triggered = true;
                    }
                }
            } else {
                overlay.superposition_depth = (overlay.superposition_depth + 0.02).min(1.0);
            }
        }

        if collapse_triggered {
            events.push(SimEvent::WavefunctionCollapse);
            state.scars.push(crate::types::StructuredScar {
                tick: state.tick,
                category: "quantum_event".to_string(),
                description: format!("Wavefunction Collapse in Zone {}", i),
                actor_id: None,
                zone_id: Some(i as u32),
                caused_by_id: None,
                metadata: serde_json::json!({
                    "event": "superposition_end"
                }),
            });
        }

        // Phase 61: Spontaneous Birth Trigger (§V9)
        // Criteria: Peak Knowledge + Extreme Order + Structural Coherence
        if state.zones[i].state.embodied_knowledge > 0.95 
           && state.zones[i].state.entropy < 0.05 
           && state.sci > 0.9 {
            // Low probability check to prevent spawning too fast
            let seed = (state.tick + i as u64) % 1000;
            if seed == 0 {
                events.push(SimEvent::Cosmogenesis);
                state.scars.push(crate::types::StructuredScar {
                    tick: state.tick,
                    category: "singularity".to_string(),
                    description: format!("COSMOGENESIS: A child universe is born in Zone {}", i),
                    actor_id: None,
                    zone_id: Some(i as u32),
                    caused_by_id: None,
                    metadata: serde_json::json!({
                        "knowledge": state.zones[i].state.embodied_knowledge,
                        "sci": state.sci
                    }),
                });
            }
        }
    }

    if state.check_meta_cycle() {
        events.push(SimEvent::MetaCycle);
    }

    // Doc 21 §8: Black Swan / Rare Event — deterministic hash(seed, tick), low probability shock.
    let seed_u = match &world.world_seed {
        Some(serde_json::Value::Number(n)) => n.as_u64().unwrap_or(0),
        _ => world.world_id.wrapping_add(state.universe_id),
    };
    let h = seed_u
        .wrapping_add(state.tick)
        .wrapping_mul(31)
        .wrapping_add(state.universe_id)
        .wrapping_mul(0x9e3779b97f4a7c15);
    if h % 10000 == 1 && !state.zones.is_empty() {
        let zone_idx = ((h / 10000) as usize) % state.zones.len();
        let shock_type = (h / 10000) % 3;
        state.zones[zone_idx].state.entropy = (state.zones[zone_idx].state.entropy + 0.2).min(1.0);
        state.zones[zone_idx].state.trauma = (state.zones[zone_idx].state.trauma + 0.15).min(1.0);
        state.zones[zone_idx].state.update_material_stress();
        let event = match shock_type {
            0 => SimEvent::BlackSwanMeteor,
            1 => SimEvent::BlackSwanPlague,
            _ => SimEvent::BlackSwanProphet,
        };
        events.push(event.clone());
        state.scars.push(crate::types::StructuredScar {
            tick: state.tick,
            category: "black_swan".to_string(),
            description: format!("Black Swan Event: {:?}", event),
            actor_id: None,
            zone_id: Some(zone_idx as u32),
            caused_by_id: None,
            metadata: serde_json::json!({
                "type": format!("{:?}", event),
                "entropy_impact": 0.2,
                "trauma_impact": 0.15
            }),
        });
    }

    // Limit cascade depth
    if events.len() > max_cascade {
        events.truncate(max_cascade);
    }
    events
}

#[cfg(test)]
mod tests {
    use super::*;
    use crate::types::{UniverseState, WorldConfig};

    fn world_config() -> WorldConfig {
        WorldConfig { 
            world_id: 1, 
            axiom: None, 
            world_seed: None, 
            origin: String::new(), 
            genome: None,
            behavior_graph: None,
            ..Default::default()
        }
    }

    fn set_high_pressure(state: &mut UniverseState, zone_idx: usize) {
        let z = &mut state.zones[zone_idx].state;
        z.entropy = 0.95;
        z.trauma = 0.95;
        z.material_stress = 0.95;
        z.inequality = 0.95;
        z.regional_scars = 1.0;
    }

    #[test]
    fn test_cascade_phase_famine_riots_collapse() {
        let world = world_config();
        let mut state = UniverseState::with_one_zone(1, 100.0);
        set_high_pressure(&mut state, 0);
        assert!(state.pressure_at_zone(0, None) >= constants::COLLAPSE_THRESHOLD);

        // Hazard model: P(phase change) = sigmoid(pressure); may need several ticks to advance.
        let mut seen_famine = false;
        let mut seen_riots = false;
        let mut seen_collapse = false;
        for _ in 0..20 {
            set_high_pressure(&mut state, 0);
            let _ev = tick_with_cascade(&mut state, &world, 20, None);
            match state.zones[0].state.cascade_phase {
                CascadePhase::Famine => seen_famine = true,
                CascadePhase::Riots => seen_riots = true,
                CascadePhase::Collapse => {
                    seen_collapse = true;
                    assert!(state.zones[0].state.active_materials.is_empty());
                    break;
                }
                _ => {}
            }
            if seen_collapse {
                break;
            }
        }
        assert!(seen_famine, "With high pressure, Famine should eventually occur");
        assert!(seen_riots, "With high pressure, Riots should eventually occur");
        assert!(seen_collapse, "With high pressure, Collapse should eventually occur");
    }
}
