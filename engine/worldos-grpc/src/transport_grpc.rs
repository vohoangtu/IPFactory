//! gRPC transport: SimulationEngine implementation.

use tonic::{Request, Response, Status};
use crate::engine;
use crate::simulation_engine_server::SimulationEngine;
use crate::{
    AdvanceRequest, AdvanceResponse, MergeRequest, MergeResponse,
    ObserveRequest, ObserveResponse, UniverseSnapshot,
    BatchAdvanceRequest, BatchAdvanceResponse,
    TrajectoryAnalysisRequest, TrajectoryAnalysisResponse,
    EvaluateRulesRequest, EvaluateRulesResponse,
    ProcessActorsSoaRequest, ProcessActorsSoaResponse,
    ProcessFieldsV7Request, ProcessFieldsV7Response,
    ComputeMetabolismGridRequest, ComputeMetabolismGridResponse,
    CalculateVocationAlignmentRequest, CalculateVocationAlignmentResponse,
    GetCombinedGravityRequest, GetCombinedGravityResponse,
};

pub struct EngineService;

#[tonic::async_trait]
impl SimulationEngine for EngineService {
    async fn advance(
        &self,
        request: Request<AdvanceRequest>,
    ) -> Result<Response<AdvanceResponse>, Status> {
        let req = request.into_inner();
        let state_input = req.state_input.as_slice();
        match engine::run_advance(req.universe_id, req.ticks, state_input, req.world_config).await {
            Ok(result) => {
                let snapshot = UniverseSnapshot {
                    universe_id: req.universe_id,
                    tick: result.tick,
                    state_vector_json: result.state_vector_json,
                    entropy: result.entropy,
                    stability_index: result.stability_index,
                    metrics_json: result.metrics_json,
                    sci: result.sci,
                    instability_gradient: result.instability_gradient,
                    global_fields_json: result.global_fields_json,
                };
                Ok(Response::new(AdvanceResponse {
                    ok: true,
                    error_message: String::new(),
                    snapshot: Some(snapshot),
                }))
            }
            Err(e) => Err(Status::invalid_argument(e)),
        }
    }

    async fn merge(
        &self,
        request: Request<MergeRequest>,
    ) -> Result<Response<MergeResponse>, Status> {
        let req = request.into_inner();
        match engine::run_merge(&req.state_a, &req.state_b) {
            Ok(result) => {
                let snapshot = UniverseSnapshot {
                    universe_id: 0,
                    tick: result.tick,
                    state_vector_json: result.state_vector_json,
                    entropy: result.entropy,
                    stability_index: result.stability_index,
                    metrics_json: result.metrics_json,
                    sci: result.sci,
                    instability_gradient: result.instability_gradient,
                    global_fields_json: result.global_fields_json,
                };
                Ok(Response::new(MergeResponse {
                    ok: true,
                    error_message: String::new(),
                    snapshot: Some(snapshot),
                }))
            }
            Err(e) => Err(Status::invalid_argument(e)),
        }
    }

    async fn observe(
        &self,
        request: Request<ObserveRequest>,
    ) -> Result<Response<ObserveResponse>, Status> {
        let req = request.into_inner();
        match engine::run_observe(req.universe_id, req.zone_index, req.intensity, &req.state_input).await {
            Ok(result) => {
                let snapshot = UniverseSnapshot {
                    universe_id: req.universe_id,
                    tick: result.tick,
                    state_vector_json: result.state_vector_json,
                    entropy: result.entropy,
                    stability_index: result.stability_index,
                    metrics_json: result.metrics_json,
                    sci: result.sci,
                    instability_gradient: result.instability_gradient,
                    global_fields_json: result.global_fields_json,
                };
                Ok(Response::new(ObserveResponse {
                    ok: true,
                    error_message: String::new(),
                    snapshot: Some(snapshot),
                }))
            }
            Err(e) => Err(Status::invalid_argument(e)),
        }
    }

    async fn batch_advance(
        &self,
        request: Request<BatchAdvanceRequest>,
    ) -> Result<Response<BatchAdvanceResponse>, Status> {
        let req = request.into_inner();
        let mut responses: Vec<AdvanceResponse> = Vec::with_capacity(req.requests.len());
        for r in req.requests {
            let state_input = r.state_input.as_slice();
            match engine::run_advance(r.universe_id, r.ticks, state_input, r.world_config).await {
                Ok(result) => {
                    responses.push(AdvanceResponse {
                        ok: true,
                        error_message: String::new(),
                        snapshot: Some(UniverseSnapshot {
                            universe_id: r.universe_id,
                            tick: result.tick,
                            state_vector_json: result.state_vector_json,
                            entropy: result.entropy,
                            stability_index: result.stability_index,
                            metrics_json: result.metrics_json,
                            sci: result.sci,
                            instability_gradient: result.instability_gradient,
                            global_fields_json: result.global_fields_json,
                        }),
                    });
                }
                Err(e) => {
                    responses.push(AdvanceResponse {
                        ok: false,
                        error_message: e,
                        snapshot: None,
                    });
                }
            }
        }
        Ok(Response::new(BatchAdvanceResponse { responses }))
    }

    async fn analyze_trajectory(
        &self,
        request: Request<TrajectoryAnalysisRequest>,
    ) -> Result<Response<TrajectoryAnalysisResponse>, Status> {
        let req = request.into_inner();
        let result = engine::run_trajectory_analysis(&req.points, req.recurrence_threshold);
        Ok(Response::new(result))
    }

    async fn evaluate_rules(
        &self,
        request: Request<EvaluateRulesRequest>,
    ) -> Result<Response<EvaluateRulesResponse>, Status> {
        let req = request.into_inner();
        let (ok, error_message, outputs_json) = engine::run_evaluate_rules(&req.state_json, &req.rules_dsl);
        Ok(Response::new(EvaluateRulesResponse {
            ok,
            error_message,
            outputs_json,
        }))
    }

    async fn process_actors_soa(
        &self,
        request: Request<ProcessActorsSoaRequest>,
    ) -> Result<Response<ProcessActorsSoaResponse>, Status> {
        let req = request.into_inner();
        let response = engine::run_process_actors_soa(
            req.tick as u64,
            req.ids.iter().map(|&x| x as u64).collect(),
            req.zone_ids.iter().map(|&x| x as u32).collect(),
            req.hunger,
            req.energy,
            req.fear,
            req.trauma,
            req.heroic_types.iter().map(|&x| x as u32).collect(),
            req.lineage_ids.iter().map(|&x| x as u64).collect(),
            req.memes.iter().map(|&x| x as u64).collect(),
            req.traits_matrix,
            req.behavior_states,
            req.behavior_graphs,
            req.archetypes,
            req.social_graph,
            req.edicts,
            req.active_sagas,
            req.faction_ids.iter().map(|&x| x as i32).collect(),
            req.faction_loyalty,
            req.is_observed,
            req.faction_relations,
            req.belief_definitions,
            req.belief_alignments,
            req.tech_definitions,
            req.actor_tech_levels,
        );

        // Phase 9: Distributed Simulation - Push state to Kafka in background
        // Fire-and-forget: the spawned task runs independently; errors are logged internally.
        let tick = req.tick;
        let response_clone = response.clone();
        tokio::spawn(async move {
            if let Err(e) = crate::kafka::send_state_update("actor-state-updates", &tick.to_string(), &response_clone).await {
                tracing::warn!("Kafka Sync Error (Tick {}): {}", tick, e);
            }
        });

        Ok(Response::new(response))
    }

    async fn process_fields_v7(
        &self,
        request: Request<ProcessFieldsV7Request>,
    ) -> Result<Response<ProcessFieldsV7Response>, Status> {
        let req = request.into_inner();
        let fields = engine::run_process_fields_v7(
            req.fields,
            req.neighbor_counts,
            req.neighbor_offsets,
            req.neighbors,
            req.diffusion_rate,
            req.preservation_rate,
        );
        Ok(Response::new(ProcessFieldsV7Response { fields }))
    }

    async fn compute_metabolism_grid(
        &self,
        request: Request<ComputeMetabolismGridRequest>,
    ) -> Result<Response<ComputeMetabolismGridResponse>, Status> {
        let req = request.into_inner();
        let response = engine::run_compute_metabolism_grid(
            req.populations,
            req.biomasses,
            req.industries,
            req.efficiency,
            req.base_energy,
        );
        Ok(Response::new(response))
    }

    async fn calculate_vocation_alignment(
        &self,
        request: Request<CalculateVocationAlignmentRequest>,
    ) -> Result<Response<CalculateVocationAlignmentResponse>, Status> {
        let req = request.into_inner();
        let alignment = engine::run_calculate_vocation_alignment(&req.actor_motivation_json, &req.target_profile_json);
        Ok(Response::new(CalculateVocationAlignmentResponse { alignment }))
    }

    async fn get_combined_gravity(
        &self,
        request: Request<GetCombinedGravityRequest>,
    ) -> Result<Response<GetCombinedGravityResponse>, Status> {
        let req = request.into_inner();
        let gravity = engine::run_get_combined_gravity(&req.rulesets_json);
        Ok(Response::new(GetCombinedGravityResponse { gravity }))
    }
}
