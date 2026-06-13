//! Engine logic: advance, merge, observe, trajectory analysis, and vectorized simulation.
//! Used by both gRPC and HTTP transports.

use worldos_core::{tick_with_cascade, KernelGenome, UniverseState, WorldConfig};
use crate::{
    RegimeTransition, TrajectoryAnalysisResponse, TrajectoryPoint, WorldConfig as GrpcWorldConfig,
    ActorSoaOutput, ProcessActorsSoaResponse, ComputeMetabolismGridResponse,
};
use crate::worldos::simulation::{AgentScar, NewActor};
use rayon::prelude::*;
use rand::SeedableRng;
use rand::Rng;

/// Result struct for `run_advance` and `run_merge`.
#[derive(Debug, Clone)]
pub struct AdvanceResult {
    pub tick: u64,
    pub state_vector_json: String,
    pub entropy: f64,
    pub stability_index: f64,
    pub metrics_json: String,
    pub sci: f64,
    pub instability_gradient: f64,
    pub global_fields_json: String,
}

/// Result struct for `run_observe`.
#[derive(Debug, Clone)]
pub struct ObserveResult {
    pub tick: u64,
    pub state_vector_json: String,
    pub entropy: f64,
    pub stability_index: f64,
    pub metrics_json: String,
    pub sci: f64,
    pub instability_gradient: f64,
    pub global_fields_json: String,
}

/// Deserialize state from bytes: JSON if starts with b'{', else bincode.
pub fn deserialize_state(state_input: &[u8]) -> Result<UniverseState, String> {
    if state_input.is_empty() {
        return Err("empty state_input".to_string());
    }
    let first = state_input[0];
    if first == b'{' {
        serde_json::from_slice(state_input).map_err(|e| format!("state_input json: {}", e))
    } else {
        bincode::deserialize(state_input).map_err(|e| format!("state_input bincode: {}", e))
    }
}

pub async fn run_advance(
    universe_id: u64,
    ticks: u64,
    state_input: &[u8],
    world_meta: Option<GrpcWorldConfig>,
) -> Result<AdvanceResult, String> {
    let mut state: UniverseState = if state_input.is_empty() {
        UniverseState::with_one_zone(universe_id, 100.0)
    } else {
        deserialize_state(state_input)?
    };

    if state.zones.is_empty() {
        let saved_tick = state.tick;
        state = UniverseState::with_one_zone(universe_id, 100.0);
        state.tick = saved_tick;
    }

    let world = if let Some(meta) = world_meta {
        WorldConfig {
            world_id: meta.world_id,
            origin: meta.origin,
            axiom: serde_json::from_str(&meta.axiom_json).ok(),
            world_seed: serde_json::from_str(&meta.world_seed_json).ok(),
            genome: meta.genome.map(|g| KernelGenome {
                diffusion_rate: g.diffusion_rate,
                entropy_coefficient: g.entropy_coefficient,
                mutation_rate: g.mutation_rate,
                attractor_gravity: g.attractor_gravity,
                complexity_bonus: g.complexity_bonus,
            }),
            behavior_graph: None,
            sharding_config: None,
        }
    } else {
        WorldConfig {
            world_id: 0,
            axiom: None,
            world_seed: None,
            origin: "generic".to_string(),
            genome: None,
            behavior_graph: None,
            sharding_config: None,
        }
    };

    for _ in 0..ticks {
        let macro_idx = state.build_macro_index();
        let _events = tick_with_cascade(&mut state, &world, 4, Some(&macro_idx));
    }

    let snap = state.to_snapshot();
    let state_vector_json = serde_json::to_string(&snap.state_vector).unwrap_or_else(|_| "{}".to_string());
    let metrics_json = serde_json::to_string(&snap.metrics).unwrap_or_else(|_| "{}".to_string());
    let entropy = snap.entropy.unwrap_or(0.0);
    let stability_index = snap.stability_index.unwrap_or(0.0);
    let sci = state.sci;
    let instability_gradient = state.instability_gradient;
    let global_fields_json = serde_json::to_string(&state.global_fields).unwrap_or_else(|_| "{}".to_string());

    // Sprint 1.2: Event Sourcing via Kafka
    if let Err(e) = crate::kafka::send_state_update(
        "worldos.sim.events",
        &universe_id.to_string(),
        &snap
    ).await {
        tracing::warn!("Kafka send_state_update failed in run_advance: {}", e);
    }
 
    Ok(AdvanceResult {
        tick: snap.tick,
        state_vector_json,
        entropy,
        stability_index,
        metrics_json,
        sci,
        instability_gradient,
        global_fields_json,
    })
}

pub fn run_merge(state_a_input: &[u8], state_b_input: &[u8]) -> Result<AdvanceResult, String> {
    let mut state_a = deserialize_state(state_a_input)?;
    let state_b = deserialize_state(state_b_input)?;
    state_a.merge(state_b);

    let snap = state_a.to_snapshot();
    let state_vector_json = serde_json::to_string(&snap.state_vector).unwrap_or_else(|_| "{}".to_string());
    let metrics_json = serde_json::to_string(&snap.metrics).unwrap_or_else(|_| "{}".to_string());
    let entropy = snap.entropy.unwrap_or(0.0);
    let stability_index = snap.stability_index.unwrap_or(0.0);
    let sci = state_a.sci;
    let instability_gradient = state_a.instability_gradient;
    let global_fields_json = serde_json::to_string(&state_a.global_fields).unwrap_or_else(|_| "{}".to_string());

    Ok(AdvanceResult {
        tick: snap.tick,
        state_vector_json,
        entropy,
        stability_index,
        metrics_json,
        sci,
        instability_gradient,
        global_fields_json,
    })
}

pub async fn run_observe(
    universe_id: u64,
    zone_index: u32,
    intensity: f64,
    state_input: &[u8],
) -> Result<ObserveResult, String> {
    let mut state: UniverseState = if state_input.is_empty() {
        UniverseState::with_one_zone(universe_id, 100.0)
    } else {
        deserialize_state(state_input)?
    };

    if state.zones.is_empty() {
        let saved_tick = state.tick;
        state = UniverseState::with_one_zone(universe_id, 100.0);
        state.tick = saved_tick;
    }

    if let Some(zone) = state.zones.get_mut(zone_index as usize) {
        zone.state.entropy = (zone.state.entropy + intensity * 0.05).min(1.0);
    }

    let world = WorldConfig::default();
    let macro_idx = state.build_macro_index();
    let _events = tick_with_cascade(&mut state, &world, 4, Some(&macro_idx));

    let snap = state.to_snapshot();
    let state_vector_json = serde_json::to_string(&snap.state_vector).unwrap_or_else(|_| "{}".to_string());
    let metrics_json = serde_json::to_string(&snap.metrics).unwrap_or_else(|_| "{}".to_string());
    let entropy = snap.entropy.unwrap_or(0.0);
    let stability_index = snap.stability_index.unwrap_or(0.0);
    let sci = state.sci;
    let instability_gradient = state.instability_gradient;
    let global_fields_json = serde_json::to_string(&state.global_fields).unwrap_or_else(|_| "{}".to_string());

    // Sprint 1.2: Event Sourcing via Kafka
    if let Err(e) = crate::kafka::send_state_update(
        "worldos.sim.events",
        &universe_id.to_string(),
        &snap
    ).await {
        tracing::warn!("Kafka send_state_update failed in run_observe: {}", e);
    }
 
    Ok(ObserveResult {
        tick: snap.tick,
        state_vector_json,
        entropy,
        stability_index,
        metrics_json,
        sci,
        instability_gradient,
        global_fields_json,
    })
}

// ═══════════════════════════════════════════════════════
// Vectorized Simulation Logic (ported from FFI)
// ═══════════════════════════════════════════════════════

pub fn run_evaluate_rules(state_json: &str, dsl: &str) -> (bool, String, String) {
    let state: serde_json::Value = serde_json::from_str(state_json).unwrap_or(serde_json::Value::Null);
    let mut rng = rand::rngs::StdRng::from_entropy();
    match worldos_rules::evaluate_rules(dsl, &state, Some(&mut rng)) {
        Ok(outputs) => {
            let out_json = serde_json::to_string(&outputs).unwrap_or("{}".to_string());
            (true, String::new(), out_json)
        }
        Err(e) => (false, format!("{:?}", e), "{}".to_string()),
    }
}

// Per-actor inputs extracted from SOA arrays.
struct ActorInputs {
    id: u64,
    z_id: u32,
    h_val: f32,
    e_val: f32,
    f_val: f32,
    t_val: f32,
    current_b_state: i32,
    archetype: String,
    current_faction: i32,
    current_loyalty: f32,
    actor_traits: Vec<f32>,
}

/// Extract per-actor inputs from the SOA arrays.
fn prepare_actor_inputs(
    i: usize,
    ids: &[u64],
    zone_ids: &[u32],
    hunger: &[f32],
    energy: &[f32],
    fear: &[f32],
    trauma: &[f32],
    behavior_states: &[i32],
    actor_archetypes: &[String],
    faction_ids: &[i32],
    faction_loyalty: &[f32],
    traits_matrix: &[f32],
    chunk_size: usize,
) -> ActorInputs {
    let id = ids[i];
    let z_id = zone_ids[i];
    let h_val = hunger[i];
    let e_val = energy[i];
    let f_val = fear[i];
    let t_val = trauma[i];
    let current_b_state = behavior_states.get(i).cloned().unwrap_or(0);
    let archetype = actor_archetypes.get(i).cloned().unwrap_or("Commoner".to_string());
    let current_faction = faction_ids.get(i).cloned().unwrap_or(-1);
    let current_loyalty = faction_loyalty.get(i).cloned().unwrap_or(0.5);
    let t_start = i * chunk_size;
    let actor_traits = if traits_matrix.len() >= t_start + chunk_size {
        traits_matrix[t_start..t_start + chunk_size].to_vec()
    } else {
        vec![0.5; 17]
    };
    ActorInputs {
        id,
        z_id,
        h_val,
        e_val,
        f_val,
        t_val,
        current_b_state,
        archetype,
        current_faction,
        current_loyalty,
        actor_traits,
    }
}

/// Compute scars and trait mutations based on final actor state.
fn compute_trait_outputs(
    tick: u64,
    inputs: &ActorInputs,
    h_delta: f32,
    _e_delta: f32,
    t_delta: f32,
    resource_delta: f32,
    fear_surge: f32,
    fear_mult: f32,
    _is_observed: bool,
) -> (Vec<AgentScar>, Vec<f32>, bool, f32, f32, f32) {
    let drift: f32 = (rand::random::<f32>() - 0.5) * 0.01;
    let final_hunger = (inputs.h_val + h_delta + drift).clamp(0.0, 1.0);
    let final_trauma = (inputs.t_val + t_delta).clamp(0.0, 1.0);
    let saga_fear = (inputs.f_val + fear_surge + drift) * fear_mult;

    let mut current_traits = inputs.actor_traits.clone();
    let mut traits_mutated = false;
    let mut actor_scars = vec![];

    if final_trauma > 0.8 && inputs.t_val < 0.6 {
        actor_scars.push(AgentScar {
            tick,
            actor_id: inputs.id,
            category: "TRAUMA".to_string(),
            description: format!("Actor {} suffered a severe emotional trauma.", inputs.id),
            raw_payload_json: format!("{{\"trauma\": {}, \"delta\": {}}}", final_trauma, t_delta),
            caused_by_id: 0,
            metadata_json: format!("{{\"entropy\": {}}}", 0.5),
        });
        current_traits[11] = (current_traits[11] + 0.05).min(1.0);
        current_traits[12] = (current_traits[12] + 0.05).min(1.0);
        traits_mutated = true;
    }

    if final_hunger > 0.95 {
        actor_scars.push(AgentScar {
            tick,
            actor_id: inputs.id,
            category: "STARVATION_THREAT".to_string(),
            description: format!("Actor {} is on the brink of starvation.", inputs.id),
            raw_payload_json: format!("{{\"hunger\": {}}}", final_hunger),
            caused_by_id: 0,
            metadata_json: "{}".to_string(),
        });
        current_traits[7] = (current_traits[7] + 0.1).min(1.0);
        current_traits[4] = (current_traits[4] - 0.05).max(0.0);
        traits_mutated = true;
    }

    if resource_delta > 5.0 {
        actor_scars.push(AgentScar {
            tick,
            actor_id: inputs.id,
            category: "SUDDEN_WEALTH".to_string(),
            description: format!("Actor {} gained significant resources suddenly.", inputs.id),
            raw_payload_json: format!("{{\"delta\": {}}}", resource_delta),
            caused_by_id: 0,
            metadata_json: "{}".to_string(),
        });
        current_traits[15] = (current_traits[15] + 0.1).min(1.0);
        current_traits[1] = (current_traits[1] + 0.05).min(1.0);
        traits_mutated = true;
    }

    (actor_scars, current_traits, traits_mutated, final_hunger, final_trauma, saga_fear)
}

/// Compute updated belief alignments for a single actor.
fn compute_belief_outputs(
    i: usize,
    count: usize,
    belief_engine: &crate::belief::BeliefEngine,
    traits_matrix: &[f32],
    belief_alignments: &[f32],
) -> Vec<f32> {
    let _belief_count = belief_engine.update_alignments(&[], &[], 0).len();
    let belief_count_actual = if count > 0 { belief_alignments.len() / count } else { 0 };
    let actor_input_beliefs = if belief_count_actual > 0 {
        &belief_alignments[i * belief_count_actual..(i + 1) * belief_count_actual]
    } else {
        &[] as &[f32]
    };
    let belief_traits = &traits_matrix[i * 17..(i + 1) * 17];
    belief_engine.update_alignments(belief_traits, actor_input_beliefs, 1)
}

/// Assemble the final per-actor output struct.
fn assemble_actor_outputs(
    i: usize,
    inputs: &ActorInputs,
    _new_b_state: i32,
    final_hunger: f32,
    e_delta: f32,
    drift: f32,
    energy_bonus: f32,
    final_trauma: f32,
    resource_delta: f32,
    mutated_traits: &[f32],
    traits_mutated: bool,
    current_faction: i32,
    current_loyalty: f32,
    actor_new_beliefs: Vec<f32>,
    tech_count: usize,
    new_tech_levels_matrix: &[f32],
    saga_fear: f32,
) -> ActorSoaOutput {
    ActorSoaOutput {
        actor_id: inputs.id,
        action_id: 0,
        new_hunger: final_hunger,
        new_energy: (inputs.e_val + e_delta + drift + energy_bonus).clamp(0.0, 1.0),
        new_trauma: final_trauma,
        resource_delta,
        new_traits: if traits_mutated { mutated_traits.to_vec() } else { vec![] },
        new_faction_ids: vec![current_faction],
        new_faction_loyalty: vec![current_loyalty],
        new_belief_alignments: actor_new_beliefs,
        new_tech_levels: if tech_count > 0 {
            new_tech_levels_matrix[i * tech_count..(i + 1) * tech_count].to_vec()
        } else {
            vec![]
        },
        intent_slug: "ACTION_PERFORMED".to_string(),
        mental_state_json: format!("{{\"hunger\":{},\"energy\":{},\"fear\":{}}}", final_hunger, inputs.e_val, saga_fear),
    }
}

pub fn run_process_actors_soa(
    tick: u64,
    ids: Vec<u64>,
    zone_ids: Vec<u32>,
    hunger: Vec<f32>,
    energy: Vec<f32>,
    fear: Vec<f32>,
    trauma: Vec<f32>,
    _heroic_types: Vec<u32>,
    _lineage_ids: Vec<u64>,
    _memes: Vec<u64>,
    traits_matrix: Vec<f32>,
    behavior_states: Vec<i32>,
    behavior_graphs: Vec<crate::worldos::simulation::BehaviorGraph>,
    actor_archetypes: Vec<String>,
    social_graph: Vec<crate::worldos::simulation::SocialEdge>,
    edicts: Vec<crate::worldos::simulation::Edict>,
    active_sagas: Vec<crate::worldos::simulation::WorldSaga>,
    faction_ids: Vec<i32>,
    faction_loyalty: Vec<f32>,
    is_observed: bool,
    faction_relations: Vec<crate::worldos::simulation::FactionRelation>,
    belief_definitions: Vec<crate::worldos::simulation::BeliefDefinition>,
    belief_alignments: Vec<f32>,
    tech_definitions: Vec<crate::worldos::simulation::TechnologyDefinition>,
    actor_tech_levels: Vec<f32>,
) -> ProcessActorsSoaResponse {
    let count = ids.len();
    
    let mut behavior_engine = crate::behavior_graph::BehaviorGraphEngine::new();
    for g in behavior_graphs {
        let mut nodes = std::collections::HashMap::new();
        for n in g.nodes {
            nodes.insert(n.id, crate::behavior_graph::BehaviorNode {
                id: n.id,
                name: n.name.clone(),
                action_type: n.action_type.clone(),
            });
        }
        let transitions = g.transitions.into_iter().map(|t| crate::behavior_graph::BehaviorTransition {
            from_node_id: t.from_node_id,
            to_node_id: t.to_node_id,
            condition: t.condition.clone(),
            weight: t.weight,
        }).collect();
        behavior_engine.add_graph(crate::behavior_graph::BehaviorGraph {
            archetype: g.archetype.clone(),
            nodes,
            transitions,
        });
    }

    let edict_registry = crate::social_impact::EdictRegistry::from_proto(&edicts);
    let social_impact = crate::social_impact::SocialImpactEngine::new();
    let diplomacy_engine = crate::diplomacy::DiplomacyEngine::new(faction_relations);
    let urban_growth_engine = crate::urban_growth::UrbanGrowthEngine::new(100);
    let belief_engine = crate::belief::BeliefEngine::new(belief_definitions);
    let tech_engine = crate::technology::TechnologyEngine::new(tech_definitions);
    
    let fears_map: std::collections::HashMap<u64, f32> = ids.iter()
        .zip(fear.iter())
        .map(|(&id, &f)| (id, f))
        .collect();

    let actor_id_to_idx: std::collections::HashMap<u64, usize> = ids.iter()
        .enumerate()
        .map(|(i, &id)| (id, i))
        .collect();
    
    let mut neighbors_map: std::collections::HashMap<u64, Vec<u64>> = std::collections::HashMap::new();
    let mut neighbor_weights: std::collections::HashMap<u64, Vec<f32>> = std::collections::HashMap::new();
    for edge in &social_graph {
        neighbors_map.entry(edge.source_id as u64).or_default().push(edge.target_id as u64);
        neighbor_weights.entry(edge.source_id as u64).or_default().push(edge.weight);
    }

    let mut fear_mult = 1.0;
    let mut energy_bonus = 0.0;
    for saga in &active_sagas {
        match saga.theme.as_str() {
            "GOLDEN_AGE" => { fear_mult *= 0.6; energy_bonus += 0.05; }
            "CATASTROPHE" => { fear_mult *= 2.0; energy_bonus -= 0.1; }
            _ => {}
        }
    }

    let new_tech_levels_matrix = tech_engine.process_step(
        count,
        &traits_matrix,
        &actor_tech_levels,
        &belief_alignments,
        &social_graph
    );
    let tech_count = if count > 0 { new_tech_levels_matrix.len() / count } else { 0 };

    if count == 0 {
        return ProcessActorsSoaResponse {
            ok: true,
            error_message: String::new(),
            outputs: vec![],
            scars: vec![],
            spawned_actors: vec![],
            civilization_metrics: None,
            calamities: vec![],
        };
    }

    let chunk_size = 17;
    let results: Vec<(ActorSoaOutput, Vec<AgentScar>, Vec<NewActor>, i32, f32)> = (0..count)
        .into_par_iter()
        .map(|i| {
            let inputs = prepare_actor_inputs(
                i, &ids, &zone_ids, &hunger, &energy, &fear, &trauma,
                &behavior_states, &actor_archetypes, &faction_ids, &faction_loyalty,
                &traits_matrix, chunk_size,
            );

            let new_b_state = behavior_engine.evaluate(
                &inputs.archetype,
                inputs.current_b_state,
                &inputs.actor_traits,
                &[inputs.h_val, inputs.e_val, inputs.f_val, inputs.t_val]
            );

            let empty_ids = vec![];
            let empty_weights = vec![];
            let n_ids = neighbors_map.get(&inputs.id).unwrap_or(&empty_ids);
            let n_weights = neighbor_weights.get(&inputs.id).unwrap_or(&empty_weights);
            let mut fear_surge = social_impact.calculate_fear_surge(n_ids, n_weights, &fears_map);
            let mut current_loyalty = inputs.current_loyalty;

            for (idx, &nb_id) in n_ids.iter().enumerate() {
                let weight = n_weights.get(idx).cloned().unwrap_or(1.0);
                if let Some(&nb_idx) = actor_id_to_idx.get(&nb_id) {
                    let nb_faction = faction_ids.get(nb_idx).cloned().unwrap_or(-1);
                    if inputs.current_faction != 0 && nb_faction == inputs.current_faction {
                        current_loyalty = (current_loyalty + 0.005 * weight).min(1.0);
                        fear_surge -= 0.01 * weight;
                    } else if inputs.current_faction != 0 && nb_faction != 0 {
                        let tension = diplomacy_engine.get_tension(inputs.current_faction, nb_faction);
                        current_loyalty = (current_loyalty - 0.002 * (0.5 + tension) * weight).max(0.0);
                        fear_surge += 0.015 * (0.5 + tension) * weight;
                    }
                }
            }

            let current_node = behavior_engine.graphs.get(&inputs.archetype)
                .and_then(|g| g.nodes.get(&new_b_state));
            let action_type = current_node.map(|n| n.action_type.as_str()).unwrap_or("Idle");

            let metabolism_mult = tech_engine.get_metabolism_multiplier(i, &new_tech_levels_matrix, tech_count);
            
            let mut h_delta = 0.03 * metabolism_mult;
            let mut e_delta = -0.02;
            let mut t_delta = -0.01 + fear_surge;
            let mut resource_delta = 0.0;
            let mut actor_spawned = vec![];

            match action_type {
                "Forage" => {
                    let extract_utility = 0.1 + (inputs.actor_traits[8] * 0.5 + inputs.actor_traits[1] * 0.3);
                    if inputs.h_val > 0.3 && extract_utility > 0.4 {
                        let yield_amt = (0.2f32 + 0.1f32 * inputs.actor_traits[7]).min(inputs.h_val);
                        h_delta -= yield_amt;
                        e_delta -= 0.15;
                        resource_delta = yield_amt;
                    }
                },
                "Flee" => {
                    e_delta -= 0.2;
                    t_delta -= 0.05;
                },
                "Socialize" => {
                    e_delta -= 0.05;
                    t_delta -= 0.02;
                },
                "Breed" => {
                    if inputs.e_val > 0.8 && inputs.h_val < 0.2 {
                        e_delta -= 0.6;
                        let mut child_traits = inputs.actor_traits.clone();
                        let mut rng = rand::rngs::StdRng::seed_from_u64(tick ^ inputs.id);
                        for (idx, t) in child_traits.iter_mut().enumerate() {
                            let mut range = match idx {
                                5 | 6 => 0.04,
                                7 | 10 => 0.20,
                                _ => 0.10,
                            };
                            if is_observed {
                                range /= 2.0;
                            }
                            let mutation = (rng.gen::<f32>() - 0.5) * range;
                            *t = (*t + mutation).clamp(0.0, 1.0);
                        }
                        actor_spawned.push(NewActor {
                            parent_id: inputs.id,
                            zone_id: inputs.z_id,
                            archetype: inputs.archetype.clone(),
                            trait_vector: child_traits,
                        });
                    }
                },
                _ => {
                    e_delta += 0.01;
                }
            }

            h_delta *= edict_registry.get_modifier("hunger_delta");
            e_delta *= edict_registry.get_modifier("energy_delta");
            t_delta *= edict_registry.get_modifier("trauma_gain");

            if inputs.f_val > 0.3 {
                t_delta += inputs.f_val * 0.1 * (0.5 + inputs.actor_traits[11]);
            }

            let (scars, mutated_traits, traits_mutated, final_hunger, final_trauma, saga_fear) = compute_trait_outputs(
                tick, &inputs, h_delta, e_delta, t_delta, resource_delta, fear_surge, fear_mult, is_observed,
            );

            let actor_new_beliefs = compute_belief_outputs(
                i, count, &belief_engine, &traits_matrix, &belief_alignments,
            );

            let output = assemble_actor_outputs(
                i, &inputs, new_b_state, final_hunger, e_delta, (rand::random::<f32>() - 0.5) * 0.01,
                energy_bonus, final_trauma, resource_delta, &mutated_traits, traits_mutated,
                inputs.current_faction, current_loyalty, actor_new_beliefs,
                tech_count, &new_tech_levels_matrix, saga_fear,
            );

            (output, scars, actor_spawned, new_b_state, saga_fear.clamp(0.0, 1.0))
        })
        .collect();

    let mut outputs = Vec::with_capacity(count);
    let mut scars = Vec::new();
    let mut spawned_actors = Vec::new();
    let mut behavior_states_output = Vec::with_capacity(count);
    let mut new_fear_vals = Vec::with_capacity(count);

    for (out, mut s, mut b, b_state, n_fear) in results {
        outputs.push(out);
        scars.append(&mut s);
        spawned_actors.append(&mut b);
        behavior_states_output.push(b_state);
        new_fear_vals.push(n_fear);
    }

    let h_vals: Vec<f32> = outputs.iter().map(|o| o.new_hunger).collect();
    let e_vals: Vec<f32> = outputs.iter().map(|o| o.new_energy).collect();
    let t_vals: Vec<f32> = outputs.iter().map(|o| o.new_trauma).collect();
    let r_deltas: Vec<f32> = outputs.iter().map(|o| o.resource_delta).collect();

    let mut civ_metrics = crate::civilization::CivilizationAggregator::aggregate(
        &zone_ids, &h_vals, &e_vals, &new_fear_vals, &t_vals, &r_deltas
    );

    let mut zone_pop_density = std::collections::HashMap::new();
    for &z in &zone_ids {
        *zone_pop_density.entry(z).or_insert(0.0f32) += 1.0;
    }
    let urban_density: Vec<f32> = civ_metrics.zone_stats.iter().map(|z| {
        let pop = zone_pop_density.get(&(z.zone_id as u32)).cloned().unwrap_or(0.0);
        urban_growth_engine.compute_density(pop, 1.0, 0.5, 0.1)
    }).collect();
    civ_metrics.urban_density = urban_density.clone();

    let max_urban_density = urban_density.into_iter().fold(0.0_f32, f32::max);
    let min_cohesion = civ_metrics.zone_stats.iter().map(|z| z.social_cohesion).fold(1.0_f32, f32::min);
    let max_extraction_rate = civ_metrics.zone_stats.iter().map(|z| z.total_resource_extracted).fold(0.0_f32, f32::max);
    let peak_tech_level = if new_tech_levels_matrix.is_empty() { 0.0 } else { new_tech_levels_matrix.iter().copied().fold(0.0_f32, f32::max) };

    let calamity_engine = crate::calamity::CalamityEngine::new();
    let calamities = calamity_engine.assess_risks(
        tick,
        civ_metrics.global_entropy,
        max_urban_density,
        max_extraction_rate,
        min_cohesion,
        peak_tech_level,
    );

    if !calamities.is_empty() {
        let (t_delta, h_delta) = calamity_engine.calculate_trauma_and_hunger_deltas(&calamities);
        for out in &mut outputs {
            out.new_trauma = (out.new_trauma + t_delta).clamp(0.0, 1.0);
            out.new_hunger = (out.new_hunger + h_delta).clamp(0.0, 1.0);
        }
    }

    ProcessActorsSoaResponse {
        ok: true,
        error_message: String::new(),
        outputs,
        scars,
        spawned_actors,
        civilization_metrics: Some(civ_metrics),
        calamities,
    }
}

pub fn run_process_fields_v7(
    fields: Vec<f64>,
    neighbor_counts: Vec<u32>,
    neighbor_offsets: Vec<u32>,
    neighbors: Vec<u32>,
    diffusion_rate: f64,
    preservation_rate: f64,
) -> Vec<f64> {
    let count = neighbor_counts.len();
    if count == 0 { return fields; }
    
    // Bảo vệ chống panic out-of-bounds khi caller (Laravel/gRPC) gửi CSR/field-array sai.
    // Trả về fields nguyên trạng (no-op an toàn) thay vì sập tiến trình gRPC server.
    if fields.len() < count * 8 || neighbor_offsets.len() < count {
        return fields;
    }
    let neighbors_len = neighbors.len();

    let mut deltas = vec![0.0; count * 8];
    for i in 0..count {
        let n_count = neighbor_counts[i] as f64;
        if n_count < 1e-9 { continue; }
        let offset = neighbor_offsets[i] as usize;
        for j in 0..(neighbor_counts[i] as usize) {
            let pos = offset + j;
            if pos >= neighbors_len { break; } // adjacency bị cắt cụt → dừng node này
            let neighbor_idx = neighbors[pos] as usize;
            if neighbor_idx >= count { continue; }
            for f in 0..8 {
                let diff = fields[neighbor_idx * 8 + f] - fields[i * 8 + f];
                deltas[i * 8 + f] += diffusion_rate * diff / n_count;
            }
        }
    }
    
    let mut new_fields = fields;
    for i in 0..count {
        for f in 0..8 {
            let idx = i * 8 + f;
            new_fields[idx] *= preservation_rate;
            new_fields[idx] = (new_fields[idx] + deltas[idx]).clamp(0.0, 1.0);
        }
    }
    new_fields
}

pub fn run_compute_metabolism_grid(
    mut populations: Vec<f64>,
    mut biomasses: Vec<f64>,
    industries: Vec<f64>,
    efficiency: f64,
    base_energy: f64,
) -> ComputeMetabolismGridResponse {
    let count = populations.len();
    if count == 0 {
        return ComputeMetabolismGridResponse { total_waste: 0.0, populations, biomasses, net_energies: vec![] };
    }

    let mut net_energies = vec![0.0; count];
    let mut total_waste = 0.0;

    for i in 0..count {
        let p = populations[i];
        let ind_act = industries[i];
        
        let gross_energy = base_energy * efficiency;
        let maintenance = (p * 0.01) + (ind_act * 0.05);
        let net_e = gross_energy - maintenance;
        net_energies[i] = net_e;
        
        if net_e < -0.5 {
            let deaths = p * 0.3;
            populations[i] = p - deaths;
            biomasses[i] += deaths * 0.05;
        }
        
        let waste_rate = 1.0 - efficiency;
        total_waste += (maintenance * waste_rate) * 0.1;
    }

    ComputeMetabolismGridResponse {
        total_waste,
        populations,
        biomasses,
        net_energies,
    }
}

pub fn run_calculate_vocation_alignment(actor_motivation_json: &str, target_profile_json: &str) -> f32 {
    let actor_motivation: worldos_core::vocation::definitions::MotivationProfile = 
        serde_json::from_str(actor_motivation_json).unwrap_or_default();
    let target_profile: worldos_core::vocation::definitions::MotivationProfile = 
        serde_json::from_str(target_profile_json).unwrap_or_default();
        
    worldos_core::vocation::scoring::calculate_vocation_alignment(&actor_motivation, &target_profile)
}

pub fn run_get_combined_gravity(rulesets_json: &str) -> f32 {
    let rulesets: Vec<worldos_core::ruleset::RuleSet> = serde_json::from_str(rulesets_json).unwrap_or_default();
    let engine = worldos_core::ruleset::RuleSetEngine { active_rulesets: rulesets };
    engine.get_combined_gravity()
}

// ═══════════════════════════════════════════════════════
// Trajectory Analysis
// ═══════════════════════════════════════════════════════

const MAX_RECURRENCE_PAIRS: u64 = 500_000;

fn euclidean_distance(a: &[f64], b: &[f64]) -> f64 {
    a.iter()
        .zip(b.iter())
        .map(|(x, y)| (x - y).powi(2))
        .sum::<f64>()
        .sqrt()
}

pub fn run_trajectory_analysis(points: &[TrajectoryPoint], threshold: f64) -> TrajectoryAnalysisResponse {
    let n = points.len();
    if n < 3 {
        return TrajectoryAnalysisResponse {
            is_bounded: true,
            is_recurrent: false,
            recurrence_rate: 0.0,
            max_lyapunov_estimate: 0.0,
            trajectory_variance: 0.0,
            basin_center: vec![],
            basin_radius: 0.0,
            regime_transitions: vec![],
        };
    }

    let threshold = if threshold <= 0.0 { 0.1 } else { threshold };
    let dim = points[0].state.len();

    let mut center = vec![0.0f64; dim];
    for p in points.iter() {
        for (i, v) in p.state.iter().enumerate() {
            if i < dim {
                center[i] += v;
            }
        }
    }
    for c in center.iter_mut() {
        *c /= n as f64;
    }

    let mut max_dist = 0.0f64;
    for p in points.iter() {
        let d = euclidean_distance(&p.state, &center);
        if d > max_dist {
            max_dist = d;
        }
    }

    let variance: f64 = points
        .iter()
        .map(|p| euclidean_distance(&p.state, &center).powi(2))
        .sum::<f64>() / n as f64;
    let mean_radius = variance.sqrt();
    let is_bounded = max_dist < mean_radius * 5.0 + 0.01;

    let total_pairs = ((n * (n - 1)) / 2) as u64;
    let (recurrence_count, total_counted, _) = if total_pairs > MAX_RECURRENCE_PAIRS {
        let step = (total_pairs / MAX_RECURRENCE_PAIRS).max(1);
        let mut recurrence_count = 0u64;
        let mut total_counted = 0u64;
        let mut t = 0u64;
        while t < total_pairs && total_counted < MAX_RECURRENCE_PAIRS {
            let mut remaining = t;
            let mut i = 0usize;
            while i < n && remaining as usize >= n - 1 - i {
                remaining -= (n - 1 - i) as u64;
                i += 1;
            }
            if i >= n {
                break;
            }
            let j = i + 1 + remaining as usize;
            if j < n {
                let d = euclidean_distance(&points[i].state, &points[j].state);
                if d < threshold {
                    recurrence_count += 1;
                }
                total_counted += 1;
            }
            t += step;
        }
        (recurrence_count, total_counted, true)
    } else {
        let mut recurrence_count = 0u64;
        for i in 0..n {
            for j in (i + 1)..n {
                let d = euclidean_distance(&points[i].state, &points[j].state);
                if d < threshold {
                    recurrence_count += 1;
                }
            }
        }
        (recurrence_count, total_pairs, false)
    };

    let recurrence_rate = if total_counted > 0 {
        recurrence_count as f64 / total_counted as f64
    } else {
        0.0
    };
    let is_recurrent = recurrence_rate > 0.05 && recurrence_rate < 0.90;

    let mut step_distances = Vec::with_capacity(n.saturating_sub(1));
    for i in 0..(n - 1) {
        step_distances.push(euclidean_distance(&points[i].state, &points[i + 1].state));
    }
    let mean_step = if step_distances.is_empty() {
        0.0
    } else {
        step_distances.iter().sum::<f64>() / step_distances.len() as f64
    };

    let mut transitions = Vec::new();
    for (i, d) in step_distances.iter().enumerate() {
        if *d > mean_step * 2.5 && mean_step > 1e-6 {
            transitions.push(RegimeTransition {
                from_tick: points[i].tick,
                to_tick: points[i + 1].tick,
                distance: *d,
            });
        }
    }

    let lyapunov = estimate_lyapunov(points);

    TrajectoryAnalysisResponse {
        is_bounded,
        is_recurrent,
        recurrence_rate,
        max_lyapunov_estimate: lyapunov,
        trajectory_variance: variance,
        basin_center: center,
        basin_radius: max_dist,
        regime_transitions: transitions,
    }
}

fn estimate_lyapunov(points: &[TrajectoryPoint]) -> f64 {
    let n = points.len();
    if n < 20 {
        return 0.0;
    }
    let look_ahead = 5.min(n / 4);
    let mut divergence_sum = 0.0f64;
    let mut count = 0u32;

    for i in 0..(n - look_ahead) {
        let mut min_dist = f64::MAX;
        let mut nearest_j = 0;
        for j in 0..(n - look_ahead) {
            if (i as isize - j as isize).unsigned_abs() < 3 {
                continue;
            }
            let d = euclidean_distance(&points[i].state, &points[j].state);
            if d < min_dist && d > 1e-10 {
                min_dist = d;
                nearest_j = j;
            }
        }
        if min_dist < f64::MAX && nearest_j + look_ahead < n {
            let evolved_dist = euclidean_distance(
                &points[i + look_ahead].state,
                &points[nearest_j + look_ahead].state,
            );
            if evolved_dist > 1e-10 && min_dist > 1e-10 {
                divergence_sum += (evolved_dist / min_dist).ln();
                count += 1;
            }
        }
    }
    if count > 0 {
        divergence_sum / (count as f64 * look_ahead as f64)
    } else {
        0.0
    }
}


#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_deserialize_state_json_roundtrip() {
        let original = UniverseState::with_one_zone(42, 100.0);
        let json = serde_json::to_vec(&original).unwrap();
        let state = deserialize_state(&json).unwrap();
        assert_eq!(state.tick, original.tick);
        assert_eq!(state.universe_id, original.universe_id);
    }

    #[test]
    fn test_deserialize_state_empty_err() {
        let result = deserialize_state(b"");
        assert!(result.is_err());
    }

    #[test]
    fn test_advance_result_fields() {
        let r = AdvanceResult {
            tick: 42,
            state_vector_json: "{}".to_string(),
            entropy: 0.5,
            stability_index: 0.8,
            metrics_json: "[]".to_string(),
            sci: 1.0,
            instability_gradient: 0.0,
            global_fields_json: "[]".to_string(),
        };
        assert_eq!(r.tick, 42);
        assert_eq!(r.entropy, 0.5);
    }

    #[test]
    fn test_run_process_actors_soa_empty() {
        let res = run_process_actors_soa(
            0, vec![], vec![], vec![], vec![], vec![], vec![],
            vec![], vec![], vec![], vec![], vec![], vec![],
            vec![], vec![], vec![], vec![], vec![], vec![], false,
            vec![], vec![], vec![], vec![], vec![],
        );
        assert!(res.ok);
        assert!(res.outputs.is_empty());
    }

    #[test]
    fn test_observe_result_fields() {
        let r = ObserveResult {
            tick: 7,
            state_vector_json: "{}".to_string(),
            entropy: 0.3,
            stability_index: 0.9,
            metrics_json: "[]".to_string(),
            sci: 0.5,
            instability_gradient: 0.1,
            global_fields_json: "[]".to_string(),
        };
        assert_eq!(r.tick, 7);
    }
}
