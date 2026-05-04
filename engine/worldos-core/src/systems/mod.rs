//! Systems module: orchestrates simulation phases.
//! Following ECS-like principles to modularize universe.rs fat tick.

pub mod layer_behavior;
pub mod local_update;
pub mod global_update;
pub mod diffusion;
pub mod post_process;
pub mod history_tracker;

use crate::types::{UniverseState, WorldConfig};
use crate::memory::ZoneActorIndex;

/// Run all simulation systems in the correct order.
pub fn run_all_systems(
    state: &mut UniverseState,
    world: &WorldConfig,
    macro_idx: Option<&ZoneActorIndex>,
) {
    // 0. Layered Behavior Pipeline
    layer_behavior::run(state, world);

    // 1. Phase 1: Local Zone Update
    local_update::run(state, world, macro_idx);

    // 2. Phase 2: Global Aggregates & Level-8 Features
    global_update::run(state, world);

    // 3. Phase 3: Diffusion & Flow
    diffusion::run(state, world, macro_idx);

    // 4. Post-Process
    post_process::run(state);

    // 5. Raw Generation (History, VIPs, Artifacts)
    history_tracker::run(state);
}
