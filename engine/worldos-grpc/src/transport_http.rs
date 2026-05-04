//! HTTP transport: axum routes and JSON handlers.

use axum::{routing::post, Json, Router};
use serde::{Deserialize, Serialize};

use crate::engine;
use crate::{KernelGenome, TrajectoryPoint, WorldConfig};

// ═══════════════════════════════════════════════════════
// Advance
// ═══════════════════════════════════════════════════════

#[derive(Debug, Deserialize)]
pub struct AdvanceHttpRequest {
    pub universe_id: u64,
    pub ticks: u64,
    #[serde(default)]
    pub state_input: Option<serde_json::Value>,
    #[serde(default)]
    pub world_config: Option<AdvanceHttpWorldConfig>,
}

#[derive(Debug, Serialize, Deserialize)]
pub struct AdvanceHttpKernelGenome {
    pub diffusion_rate: f64,
    pub entropy_coefficient: f64,
    pub mutation_rate: f64,
    pub attractor_gravity: f64,
    pub complexity_bonus: f64,
}

impl Default for AdvanceHttpKernelGenome {
    fn default() -> Self {
        Self {
            diffusion_rate: 0.05,
            entropy_coefficient: 1.0,
            mutation_rate: 0.05,
            attractor_gravity: 1.0,
            complexity_bonus: 1.0,
        }
    }
}

#[derive(Debug, Serialize, Deserialize)]
pub struct AdvanceHttpWorldConfig {
    pub world_id: u64,
    pub origin: String,
    pub axiom: Option<serde_json::Value>,
    pub world_seed: Option<serde_json::Value>,
    #[serde(default)]
    pub genome: Option<AdvanceHttpKernelGenome>,
}

#[derive(Debug, Serialize)]
pub struct AdvanceHttpSnapshot {
    pub universe_id: u64,
    pub tick: u64,
    pub state_vector: String,
    pub entropy: f64,
    pub stability_index: f64,
    pub metrics: String,
    pub sci: f64,
    pub instability_gradient: f64,
    pub global_fields: String,
}

#[derive(Debug, Serialize)]
pub struct AdvanceHttpResponse {
    pub ok: bool,
    pub error_message: String,
    pub snapshot: Option<AdvanceHttpSnapshot>,
}

async fn advance_http(Json(body): Json<AdvanceHttpRequest>) -> Json<AdvanceHttpResponse> {
    let state_bytes = body
        .state_input
        .as_ref()
        .map(|v| serde_json::to_vec(v).unwrap_or_default())
        .unwrap_or_default();

    let world_meta = body.world_config.map(|wc| {
        let g = wc.genome.unwrap_or_default();
        WorldConfig {
            world_id: wc.world_id,
            origin: wc.origin,
            axiom_json: wc.axiom.map(|v| v.to_string()).unwrap_or_else(|| "{}".to_string()),
            world_seed_json: wc.world_seed.map(|v| v.to_string()).unwrap_or_else(|| "{}".to_string()),
            genome: Some(KernelGenome {
                diffusion_rate: g.diffusion_rate,
                entropy_coefficient: g.entropy_coefficient,
                mutation_rate: g.mutation_rate,
                attractor_gravity: g.attractor_gravity,
                complexity_bonus: g.complexity_bonus,
            }),
        }
    });

    match engine::run_advance(body.universe_id, body.ticks, &state_bytes, world_meta).await {
        Ok(result) => {
            Json(AdvanceHttpResponse {
                ok: true,
                error_message: String::new(),
                snapshot: Some(AdvanceHttpSnapshot {
                    universe_id: body.universe_id,
                    tick: result.tick,
                    state_vector: result.state_vector_json,
                    entropy: result.entropy,
                    stability_index: result.stability_index,
                    metrics: result.metrics_json,
                    sci: result.sci,
                    instability_gradient: result.instability_gradient,
                    global_fields: result.global_fields_json,
                }),
            })
        }
        Err(e) => Json(AdvanceHttpResponse {
            ok: false,
            error_message: e,
            snapshot: None,
        }),
    }
}

// ═══════════════════════════════════════════════════════
// Observe
// ═══════════════════════════════════════════════════════

#[derive(Debug, Deserialize)]
pub struct ObserveHttpRequest {
    pub universe_id: u64,
    pub zone_index: u32,
    pub intensity: f64,
    pub state_input: String,
}

async fn observe_http(Json(body): Json<ObserveHttpRequest>) -> Json<AdvanceHttpResponse> {
    match engine::run_observe(
        body.universe_id,
        body.zone_index,
        body.intensity,
        body.state_input.as_bytes(),
    ).await {
        Ok(result) => {
            Json(AdvanceHttpResponse {
                ok: true,
                error_message: String::new(),
                snapshot: Some(AdvanceHttpSnapshot {
                    universe_id: body.universe_id,
                    tick: result.tick,
                    state_vector: result.state_vector_json,
                    entropy: result.entropy,
                    stability_index: result.stability_index,
                    metrics: result.metrics_json,
                    sci: result.sci,
                    instability_gradient: result.instability_gradient,
                    global_fields: result.global_fields_json,
                }),
            })
        }
        Err(e) => Json(AdvanceHttpResponse {
            ok: false,
            error_message: e,
            snapshot: None,
        }),
    }
}

// ═══════════════════════════════════════════════════════
// Merge
// ═══════════════════════════════════════════════════════

#[derive(Debug, Deserialize)]
pub struct MergeHttpRequest {
    pub state_a: String,
    pub state_b: String,
}

async fn merge_http(Json(body): Json<MergeHttpRequest>) -> Json<AdvanceHttpResponse> {
    match engine::run_merge(body.state_a.as_bytes(), body.state_b.as_bytes()) {
        Ok(result) => {
            Json(AdvanceHttpResponse {
                ok: true,
                error_message: String::new(),
                snapshot: Some(AdvanceHttpSnapshot {
                    universe_id: 0,
                    tick: result.tick,
                    state_vector: result.state_vector_json,
                    entropy: result.entropy,
                    stability_index: result.stability_index,
                    metrics: result.metrics_json,
                    sci: result.sci,
                    instability_gradient: result.instability_gradient,
                    global_fields: result.global_fields_json,
                }),
            })
        }
        Err(e) => Json(AdvanceHttpResponse {
            ok: false,
            error_message: e,
            snapshot: None,
        }),
    }
}

// ═══════════════════════════════════════════════════════
// BatchAdvance
// ═══════════════════════════════════════════════════════

#[derive(Debug, Deserialize)]
pub struct BatchAdvanceHttpRequest {
    pub requests: Vec<AdvanceHttpRequest>,
}

#[derive(Debug, Serialize)]
pub struct BatchAdvanceHttpResponse {
    pub responses: Vec<AdvanceHttpResponse>,
}

async fn batch_advance_http(Json(body): Json<BatchAdvanceHttpRequest>) -> Json<BatchAdvanceHttpResponse> {
    let mut responses = Vec::with_capacity(body.requests.len());
    for req in body.requests {
        let state_bytes = req
            .state_input
            .as_ref()
            .map(|v| serde_json::to_vec(v).unwrap_or_default())
            .unwrap_or_default();

        let world_meta = req.world_config.map(|wc| {
            let g = wc.genome.unwrap_or_default();
            WorldConfig {
                world_id: wc.world_id,
                origin: wc.origin,
                axiom_json: wc.axiom.map(|v| v.to_string()).unwrap_or_else(|| "{}".to_string()),
                world_seed_json: wc.world_seed.map(|v| v.to_string()).unwrap_or_else(|| "{}".to_string()),
                genome: Some(KernelGenome {
                    diffusion_rate: g.diffusion_rate,
                    entropy_coefficient: g.entropy_coefficient,
                    mutation_rate: g.mutation_rate,
                    attractor_gravity: g.attractor_gravity,
                    complexity_bonus: g.complexity_bonus,
                }),
            }
        });

        match engine::run_advance(req.universe_id, req.ticks, &state_bytes, world_meta).await {
            Ok(result) => {
                responses.push(AdvanceHttpResponse {
                    ok: true,
                    error_message: String::new(),
                    snapshot: Some(AdvanceHttpSnapshot {
                        universe_id: req.universe_id,
                        tick: result.tick,
                        state_vector: result.state_vector_json,
                        entropy: result.entropy,
                        stability_index: result.stability_index,
                        metrics: result.metrics_json,
                        sci: result.sci,
                        instability_gradient: result.instability_gradient,
                        global_fields: result.global_fields_json,
                    }),
                });
            }
            Err(e) => {
                responses.push(AdvanceHttpResponse {
                    ok: false,
                    error_message: e,
                    snapshot: None,
                });
            }
        }
    }

    Json(BatchAdvanceHttpResponse { responses })
}

// ═══════════════════════════════════════════════════════
// AnalyzeTrajectory
// ═══════════════════════════════════════════════════════

#[derive(Debug, Deserialize)]
pub struct TrajectoryPointHttp {
    pub tick: u64,
    pub state: Vec<f64>,
}

#[derive(Debug, Deserialize)]
pub struct TrajectoryAnalysisHttpRequest {
    pub points: Vec<TrajectoryPointHttp>,
    #[serde(default = "default_threshold")]
    pub recurrence_threshold: f64,
}

fn default_threshold() -> f64 {
    0.1
}

#[derive(Debug, Serialize)]
pub struct RegimeTransitionHttp {
    pub from_tick: u64,
    pub to_tick: u64,
    pub distance: f64,
}

#[derive(Debug, Serialize)]
pub struct TrajectoryAnalysisHttpResponse {
    pub is_bounded: bool,
    pub is_recurrent: bool,
    pub recurrence_rate: f64,
    pub max_lyapunov_estimate: f64,
    pub trajectory_variance: f64,
    pub basin_center: Vec<f64>,
    pub basin_radius: f64,
    pub regime_transitions: Vec<RegimeTransitionHttp>,
}

async fn analyze_trajectory_http(Json(body): Json<TrajectoryAnalysisHttpRequest>) -> Json<TrajectoryAnalysisHttpResponse> {
    let proto_points: Vec<TrajectoryPoint> = body
        .points
        .into_iter()
        .map(|p| TrajectoryPoint {
            tick: p.tick,
            state: p.state,
        })
        .collect();

    let result = engine::run_trajectory_analysis(&proto_points, body.recurrence_threshold);

    Json(TrajectoryAnalysisHttpResponse {
        is_bounded: result.is_bounded,
        is_recurrent: result.is_recurrent,
        recurrence_rate: result.recurrence_rate,
        max_lyapunov_estimate: result.max_lyapunov_estimate,
        trajectory_variance: result.trajectory_variance,
        basin_center: result.basin_center,
        basin_radius: result.basin_radius,
        regime_transitions: result
            .regime_transitions
            .into_iter()
            .map(|t| RegimeTransitionHttp {
                from_tick: t.from_tick,
                to_tick: t.to_tick,
                distance: t.distance,
            })
            .collect(),
    })
}

// ═══════════════════════════════════════════════════════
// Evaluate Rules
// ═══════════════════════════════════════════════════════

#[derive(Debug, Deserialize)]
pub struct EvaluateRulesHttpRequest {
    pub state: serde_json::Value,
    #[serde(default)]
    pub rules_dsl: Option<String>,
}

#[derive(Debug, Serialize)]
pub struct EvaluateRulesHttpResponse {
    pub ok: bool,
    pub error_message: String,
    pub outputs: serde_json::Value,
}

async fn evaluate_rules_http(Json(body): Json<EvaluateRulesHttpRequest>) -> Json<EvaluateRulesHttpResponse> {
    let state_json = body.state.to_string();
    let dsl = body.rules_dsl.unwrap_or_default();
    let (ok, error, outputs_json) = engine::run_evaluate_rules(&state_json, &dsl);
    Json(EvaluateRulesHttpResponse {
        ok,
        error_message: error,
        outputs: serde_json::from_str(&outputs_json).unwrap_or(serde_json::Value::Null),
    })
}

// ═══════════════════════════════════════════════════════
// Process Actors SoA
// ═══════════════════════════════════════════════════════

#[derive(Debug, Deserialize)]
pub struct ProcessActorsSoaHttpRequest {
    pub tick: u64,
    pub ids: Vec<u64>,
    pub zone_ids: Vec<u32>,
    pub hunger: Vec<f32>,
    pub energy: Vec<f32>,
    pub fear: Vec<f32>,
    pub trauma: Vec<f32>,
    pub heroic_types: Vec<u32>,
    pub lineage_ids: Vec<u64>,
    pub memes: Vec<u64>,
    pub traits_matrix: Vec<f32>,
    pub behavior_states: Vec<i32>,
    // we omit complex types for now or use JSON if needed
    #[serde(default)]
    pub actor_archetypes: Vec<String>,
    #[serde(default)]
    pub faction_ids: Vec<i32>,
    #[serde(default)]
    pub faction_loyalty: Vec<f32>,
    #[serde(default)]
    pub is_observed: bool,
    #[serde(default)]
    pub belief_alignments: Vec<f32>,
    #[serde(default)]
    pub actor_tech_levels: Vec<f32>,
}

#[derive(Debug, Serialize)]
pub struct ActorSoaOutputHttp {
    pub action_id: u32,
    pub new_hunger: f32,
    pub new_energy: f32,
    pub new_trauma: f32,
}

#[derive(Debug, Serialize)]
pub struct ProcessActorsSoaHttpResponse {
    pub ok: bool,
    pub error_message: String,
    pub outputs: Vec<ActorSoaOutputHttp>,
}

async fn process_actors_soa_http(Json(body): Json<ProcessActorsSoaHttpRequest>) -> Json<ProcessActorsSoaHttpResponse> {
    let res = engine::run_process_actors_soa(
        body.tick, body.ids, body.zone_ids, body.hunger, body.energy,
        body.fear, body.trauma, body.heroic_types, body.lineage_ids, body.memes,
        body.traits_matrix, body.behavior_states, vec![], body.actor_archetypes,
        vec![], vec![], vec![], body.faction_ids, body.faction_loyalty,
        body.is_observed, vec![], vec![], body.belief_alignments,
        vec![], body.actor_tech_levels
    );
    Json(ProcessActorsSoaHttpResponse {
        ok: res.ok,
        error_message: res.error_message,
        outputs: res.outputs.into_iter().map(|o| ActorSoaOutputHttp {
            action_id: o.action_id,
            new_hunger: o.new_hunger,
            new_energy: o.new_energy,
            new_trauma: o.new_trauma,
        }).collect(),
    })
}

// ═══════════════════════════════════════════════════════
// Process Fields
// ═══════════════════════════════════════════════════════

#[derive(Debug, Deserialize)]
pub struct ProcessFieldsHttpRequest {
    pub fields: Vec<f64>,
    pub neighbor_counts: Vec<u32>,
    pub neighbor_offsets: Vec<u32>,
    pub neighbors: Vec<u32>,
    pub diffusion_rate: f64,
    pub preservation_rate: f64,
}

async fn process_fields_http(Json(body): Json<ProcessFieldsHttpRequest>) -> Json<Vec<f64>> {
    Json(engine::run_process_fields_v7(
        body.fields, body.neighbor_counts, body.neighbor_offsets,
        body.neighbors, body.diffusion_rate, body.preservation_rate
    ))
}

// ═══════════════════════════════════════════════════════
// Compute Metabolism
// ═══════════════════════════════════════════════════════

#[derive(Debug, Deserialize)]
pub struct ComputeMetabolismHttpRequest {
    pub populations: Vec<f64>,
    pub biomasses: Vec<f64>,
    pub industries: Vec<f64>,
    pub efficiency: f64,
    pub base_energy: f64,
}

#[derive(Debug, Serialize)]
pub struct ComputeMetabolismHttpResponse {
    pub total_waste: f64,
    pub net_energies: Vec<f64>,
    pub populations: Vec<f64>,
    pub biomasses: Vec<f64>,
}

async fn compute_metabolism_http(Json(body): Json<ComputeMetabolismHttpRequest>) -> Json<ComputeMetabolismHttpResponse> {
    let res = engine::run_compute_metabolism_grid(
        body.populations, body.biomasses, body.industries,
        body.efficiency, body.base_energy
    );
    Json(ComputeMetabolismHttpResponse {
        total_waste: res.total_waste,
        net_energies: res.net_energies,
        populations: res.populations,
        biomasses: res.biomasses,
    })
}

// ═══════════════════════════════════════════════════════
// Vocation & Gravity
// ═══════════════════════════════════════════════════════

#[derive(Debug, Deserialize)]
pub struct CalculationRequest {
    pub actor_motivation: serde_json::Value,
    pub target_profile: serde_json::Value,
}

async fn calculate_vocation_alignment_http(Json(body): Json<CalculationRequest>) -> Json<serde_json::Value> {
    let alignment = engine::run_calculate_vocation_alignment(
        &body.actor_motivation.to_string(),
        &body.target_profile.to_string()
    );
    Json(serde_json::json!({ "alignment": alignment }))
}

#[derive(Debug, Deserialize)]
pub struct GravityRequest {
    pub rulesets: serde_json::Value,
}

async fn get_combined_gravity_http(Json(body): Json<GravityRequest>) -> Json<serde_json::Value> {
    let gravity = engine::run_get_combined_gravity(&body.rulesets.to_string());
    Json(serde_json::json!({ "gravity": gravity }))
}

// ═══════════════════════════════════════════════════════
// Router
// ═══════════════════════════════════════════════════════

pub fn router() -> Router {
    Router::new()
        .route("/advance", post(advance_http))
        .route("/merge", post(merge_http))
        .route("/observe", post(observe_http))
        .route("/batch-advance", post(batch_advance_http))
        .route("/analyze-trajectory", post(analyze_trajectory_http))
        .route("/evaluate-rules", post(evaluate_rules_http))
        .route("/process-actors-soa", post(process_actors_soa_http))
        .route("/process-fields", post(process_fields_http))
        .route("/compute-metabolism", post(compute_metabolism_http))
        .route("/calculate-vocation-alignment", post(calculate_vocation_alignment_http))
        .route("/get-combined-gravity", post(get_combined_gravity_http))
}
