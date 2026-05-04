use crate::types::*;
use rand::Rng;

/// Evaluates state to emit spontaneous Raw Items
/// E.g. Epoch Shifts, new Celebrities, new Artifacts based on zone pressures and thresholds.
pub fn run(state: &mut UniverseState) {
    let mut rng = rand::thread_rng();
    let tick = state.tick;
    
    // Clear out old pendings (they should be consumed by callers each tick)
    // Actually, normally the orchestrator (PHP) reads them and then we clear them. 
    // Here we clear them at the BEGINNING of a tick, so the output contains only the events of this tick.
    state.pending_history_events.clear();
    state.pending_celebrities.clear();
    state.pending_artifacts.clear();

    for zone_serial in &state.zones {
        let z = &zone_serial.state;
        let zid = zone_serial.id;

        // 1. Check for History Events
        // If trauma is extremely high, history event: "Tragedy / Collapse"
        if z.trauma > 0.9 && rng.gen_bool(0.1) {
            state.pending_history_events.push(HistoricalEventRaw {
                tick,
                zone_id: zid,
                event_type: "collapse".to_string(),
                impact_score: z.trauma as f64,
                trigger_data: serde_json::json!({"trauma": z.trauma}),
            });
        }
        
        // If knowledge frontier makes a big leap (e.g. hits a multiple of 1.0)
        // Or if it's very high.
        if z.knowledge_frontier > 0.8 && rng.gen_bool(0.05) {
            state.pending_history_events.push(HistoricalEventRaw {
                tick,
                zone_id: zid,
                event_type: "golden_age".to_string(),
                impact_score: z.knowledge_frontier as f64,
                trigger_data: serde_json::json!({"knowledge": z.knowledge_frontier}),
            });
        }

        // 2. Discover Celebrities
        // When cultural innovation and institutional respect are high, create a great thinker/leader
        // Assuming every tick there is a small chance
        if z.cultural.innovation_openness > 0.85 && rng.gen_bool(0.02) {
            state.pending_celebrities.push(CelebrityRaw {
                id: rng.gen::<u64>(),
                zone_id: zid,
                fame: 0.8,
                vocation: "Architect of Thought".to_string(),
                origin_tick: tick,
            });
        }
        if z.civ_fields.power > 0.8 && rng.gen_bool(0.02) {
            state.pending_celebrities.push(CelebrityRaw {
                id: rng.gen::<u64>(),
                zone_id: zid,
                fame: 0.9,
                vocation: "Supreme Ruler".to_string(),
                origin_tick: tick,
            });
        }

        // 3. Discover Artifacts
        // When structured mass increases suddenly or free energy is very high along with knowledge
        if z.free_energy > 50.0 && z.embodied_knowledge > 0.7 && rng.gen_bool(0.01) {
            state.pending_artifacts.push(ArtifactRaw {
                id: rng.gen::<u64>(),
                zone_id: zid,
                mass: rng.gen_range(1.0..5.0),
                knowledge_encoded: z.embodied_knowledge as f64,
                origin_tick: tick,
            });
        }
    }
}
