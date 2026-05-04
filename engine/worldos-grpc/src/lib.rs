pub mod worldos {
    pub mod simulation {
        include!(concat!(env!("OUT_DIR"), "/worldos.simulation.rs"));
    }
}

pub mod engine;
pub mod behavior_graph;
pub mod social_impact;
pub mod civilization;
pub mod diplomacy;
pub mod urban_growth;
pub mod belief;
pub mod technology;
pub mod calamity;
pub mod transport_grpc;
pub mod transport_http;
pub mod kafka;

pub use worldos::simulation::simulation_engine_server;
pub use worldos::simulation::{
    AdvanceRequest, AdvanceResponse, UniverseSnapshot, WorldConfig, KernelGenome,
    MergeRequest, MergeResponse, ObserveRequest, ObserveResponse,
    BatchAdvanceRequest, BatchAdvanceResponse,
    TrajectoryAnalysisRequest, TrajectoryAnalysisResponse, TrajectoryPoint, RegimeTransition,
    // Vectorized Simulation Messages
    EvaluateRulesRequest, EvaluateRulesResponse,
    ProcessActorsSoaRequest, ProcessActorsSoaResponse, ActorSoaOutput,
    ProcessFieldsV7Request, ProcessFieldsV7Response,
    ComputeMetabolismGridRequest, ComputeMetabolismGridResponse,
    CalculateVocationAlignmentRequest, CalculateVocationAlignmentResponse,
    GetCombinedGravityRequest, GetCombinedGravityResponse,
};
