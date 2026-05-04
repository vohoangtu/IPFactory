use crate::types::{UniverseState, WorldConfig, StructuredScar};

/// System for Phase 2: Global Aggregates and Level-8 Features.
/// Processes aggregates (Entropy, SCI), Archetypes, Narratives, and Attractors.
pub fn run(state: &mut UniverseState, _world: &WorldConfig) {
    let n_len = state.zones.len();
    if n_len > 0 {
        let n = n_len as f64;
        state.global_entropy = state.zones.iter().map(|z| z.state.entropy).sum::<f64>() / n;
        state.knowledge_core = state.zones.iter().map(|z| z.state.embodied_knowledge).sum::<f64>() / n;
        
        // SCI = 1.0 - (average material stress * entropy weight)
        let avg_stress = state.zones.iter().map(|z| z.state.material_stress).sum::<f64>() / n;
        state.sci = (1.0 - (avg_stress * 0.4 + state.global_entropy * 0.2)).clamp(0.0, 1.0);
        
        // Toggle Micro Mode if gradient is high
        state.instability_gradient = (avg_stress - 0.5).max(0.0) * 2.0; 

        // Macro Field Evolution (Phase 4 Purification)
        let macro_engine = crate::macro_fields::MacroFieldEngine::new();
        macro_engine.update(state);

        // Zone Potential Fields (Phase 4 Purification)
        let potential_engine = crate::potential_fields::PotentialFieldEngine::new();
        potential_engine.update(state);
    }

    // Level-8: Archetype Discovery — recognize civilization patterns
    let prev_archetype = state.archetype_discovery.clone();
    state.run_archetype_discovery();
    if let Some(discovery) = &state.archetype_discovery {
        let is_new = prev_archetype.map(|p| p.name != discovery.name).unwrap_or(true);
        if is_new {
            state.scars.push(StructuredScar {
                tick: state.tick,
                category: "archetype_discovery".to_string(),
                description: format!("Civilization Pattern Recognized: {}", discovery.name),
                actor_id: None,
                zone_id: None,
                caused_by_id: None,
                metadata: serde_json::json!({
                    "archetype": discovery.name,
                    "distance": discovery.distance,
                    "is_novel": discovery.is_novel
                }),
            });
        }
    }

    // Level-8: Narrative Synthesis
    let culture_engine = crate::culture_engine::CultureEngine::new(0.01);
    state.narrative_tags = culture_engine.generate_narrative_tags(&state.zones);

    // Level-8: Attractor Field Engine — zones pulled by civilization attractors
    state.apply_attractor_fields();
    // Level-8: Dark Attractor — high entropy/trauma/inequality pulls toward collapse
    state.apply_dark_attractors();
    // Level-8: Intelligence Explosion — knowledge/energy/openness boost
    state.apply_intelligence_explosion();

    // Level-8: Phase Transition — Tribal → Agrarian → ... → Information
    for z in &mut state.zones {
        UniverseState::check_phase_transition(&mut z.state);
    }
}

/// Helper to refresh global aggregates (mirrors refresh_aggregates in universe.rs).
pub fn refresh_aggregates(state: &mut UniverseState) {
    let n = state.zones.len() as f64;
    if n <= 0.0 {
        return;
    }

    state.global_entropy = state.zones.iter().map(|z| z.state.entropy).sum::<f64>() / n;
    state.knowledge_core = state.zones.iter().map(|z| z.state.embodied_knowledge).sum::<f64>() / n;
    
    let avg_stress: f64 = state.zones.iter().map(|z| z.state.material_stress).sum::<f64>() / n;
    state.sci = (1.0 - (avg_stress * 0.4 + state.global_entropy * 0.2)).clamp(0.0, 1.0);
    state.instability_gradient = (avg_stress - 0.5).max(0.0) * 2.0;

    state.global_fields.survival = state.zones.iter().map(|z| z.state.civ_fields.survival).sum::<f64>() / n;
    state.global_fields.reproduction = state.zones.iter().map(|z| z.state.civ_fields.reproduction).sum::<f64>() / n;
    state.global_fields.wealth = state.zones.iter().map(|z| z.state.civ_fields.wealth).sum::<f64>() / n;
    state.global_fields.power = state.zones.iter().map(|z| z.state.civ_fields.power).sum::<f64>() / n;
    state.global_fields.knowledge = state.zones.iter().map(|z| z.state.civ_fields.knowledge).sum::<f64>() / n;
    state.global_fields.meaning = state.zones.iter().map(|z| z.state.civ_fields.meaning).sum::<f64>() / n;
    state.global_fields.status = state.zones.iter().map(|z| z.state.civ_fields.status).sum::<f64>() / n;
    state.global_fields.belonging = state.zones.iter().map(|z| z.state.civ_fields.belonging).sum::<f64>() / n;
}
