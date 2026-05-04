use crate::types::UniverseState;
use crate::systems::global_update;

/// System for Post-Processing.
/// Processes Quantum Overlays, Vocation Drift, and final Aggregate refresh.
pub fn run(state: &mut UniverseState) {
    // V7: Quantum Overlay — decay superposition per tick
    state.tick_quantum_overlays();

    // Phase 4: Vocation & Motivation Drift (§Phase 4 Synthesis)
    state.tick_vocation_drift();

    // Final Aggregate refresh
    global_update::refresh_aggregates(state);
    
    // Increment tick
    state.tick += 1;
}
