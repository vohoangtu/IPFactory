//! gRPC request/response serialization round-trip tests.

use prost::Message;
use worldos_grpc::{
    AdvanceRequest, UniverseSnapshot, WorldConfig, KernelGenome,
    ObserveResponse,
};

#[test]
fn advance_request_roundtrip() {
    let original = AdvanceRequest {
        universe_id: 42,
        ticks: 10,
        state_input: vec![1, 2, 3],
        world_config: Some(WorldConfig {
            world_id: 1,
            origin: "test".to_string(),
            axiom_json: "{}".to_string(),
            world_seed_json: "{}".to_string(),
            genome: Some(KernelGenome {
                diffusion_rate: 0.1,
                entropy_coefficient: 0.2,
                mutation_rate: 0.3,
                attractor_gravity: 0.4,
                complexity_bonus: 0.5,
            }),
        }),
    };

    let encoded = original.encode_to_vec();
    let decoded = AdvanceRequest::decode(encoded.as_slice()).expect("decode should succeed");

    assert_eq!(original.universe_id, decoded.universe_id);
    assert_eq!(original.ticks, decoded.ticks);
    assert_eq!(original.state_input, decoded.state_input);
    let orig_cfg = original.world_config.unwrap();
    let dec_cfg = decoded.world_config.unwrap();
    assert_eq!(orig_cfg.world_id, dec_cfg.world_id);
    assert_eq!(orig_cfg.origin, dec_cfg.origin);
}

#[test]
fn observe_response_roundtrip() {
    let original = ObserveResponse {
        ok: true,
        error_message: String::new(),
        snapshot: Some(UniverseSnapshot {
            universe_id: 7,
            tick: 99,
            state_vector_json: "{\"x\":1}".to_string(),
            entropy: 0.5,
            stability_index: 0.8,
            metrics_json: "{}".to_string(),
            sci: 0.1,
            instability_gradient: 0.2,
            global_fields_json: "{}".to_string(),
        }),
    };

    let encoded = original.encode_to_vec();
    let decoded = ObserveResponse::decode(encoded.as_slice()).expect("decode should succeed");

    assert_eq!(original.ok, decoded.ok);
    assert_eq!(original.error_message, decoded.error_message);
    let orig_snap = original.snapshot.unwrap();
    let dec_snap = decoded.snapshot.unwrap();
    assert_eq!(orig_snap.universe_id, dec_snap.universe_id);
    assert_eq!(orig_snap.tick, dec_snap.tick);
    assert_eq!(orig_snap.state_vector_json, dec_snap.state_vector_json);
    assert_eq!(orig_snap.entropy, dec_snap.entropy);
    assert_eq!(orig_snap.stability_index, dec_snap.stability_index);
}
