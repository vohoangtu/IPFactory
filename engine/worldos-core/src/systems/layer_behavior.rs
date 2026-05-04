use crate::types::{UniverseState, WorldConfig, EmotionField};

/// System for processing layered behavior pipeline.
/// Covers Macro (Emotions), Social (Beliefs, Power, Culture), Meso (Crowds), and Micro (Behavior Graph) layers.
pub fn run(state: &mut UniverseState, world: &WorldConfig) {
    // Ensure behavior_context has enough zones
    if state.behavior_context.emotion_fields.len() < state.zones.len() {
        state.behavior_context.emotion_fields.resize(state.zones.len(), EmotionField::default());
    }

    // 0. LAYERED BEHAVIOR PIPELINE (Phase 3 Architecture)
    
    // A. Macro Layer: Update Emotion Fields (Diffusion/Decay)
    let emotion_engine = crate::emotion_field::EmotionFieldEngine::new(0.01);
    emotion_engine.update(&mut state.behavior_context, &state.actor_table);

    // B. Social/Cognitive Layer: Update Beliefs and Power structures
    let belief_engine = crate::social_layers::BeliefSystemEngine::new(0.05);
    belief_engine.update(&mut state.actor_table, &mut state.behavior_context);

    let power_engine = crate::social_layers::PowerStructureEngine::new(0.1);
    power_engine.apply_coercion(&mut state.actor_table, &state.behavior_context);

    let culture_engine = crate::culture_engine::CultureEngine::new(0.02);
    culture_engine.update(&mut state.actor_table, &state.behavior_context, &state.zones);

    // C. Meso Layer: Cluster Crowds and Apply Crowd Dynamics
    let mut mass_engine = crate::mass_behavior::MassBehaviorEngine::new();
    mass_engine.detect_crowds(&state.actor_table, &state.behavior_context);
    mass_engine.apply_dynamics(&mut state.actor_table, &state.behavior_context);

    // D. Micro Layer: Evaluate Behavior Graph (Data-Driven)
    let nodes = world.behavior_graph.clone().unwrap_or_default();
    let mut micro_engine = crate::behavior_graph::BehaviorGraphEngine::new(nodes); 
    micro_engine.evaluate(&mut state.actor_table, &state.behavior_context);
}
