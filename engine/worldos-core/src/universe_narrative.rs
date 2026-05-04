use crate::types::*;

impl UniverseState {
    /// Apply narrative-driven influences to the universe state before ticking.
    pub fn apply_narrative_influence(&mut self, influence: &serde_json::Value) {
        if let Some(inf_type) = influence.get("type").and_then(|v| v.as_str()) {
            match inf_type {
                "dark_attractor" => {
                    if let Ok(da) = serde_json::from_value::<DarkAttractor>(influence.clone()) {
                        self.dark_attractors.push(da);
                    }
                },
                "emotion_spike" => {
                    if let Some(zone_id) = influence.get("zone_id").and_then(|v| v.as_u64()) {
                        let zone_id = zone_id as usize;
                        if zone_id < self.behavior_context.emotion_fields.len() {
                            let field = &mut self.behavior_context.emotion_fields[zone_id];
                            if let Some(fear) = influence.get("fear").and_then(|v| v.as_f64()) {
                                field.fear = (field.fear + fear as f32).clamp(0.0, 1.0);
                            }
                            if let Some(anger) = influence.get("anger").and_then(|v| v.as_f64()) {
                                field.anger = (field.anger + anger as f32).clamp(0.0, 1.0);
                            }
                        }
                    }
                },
                "narrative_tag" => {
                    if let Ok(tag) = serde_json::from_value::<NarrativeTag>(influence.clone()) {
                        self.narrative_tags.push(tag);
                    }
                },
                "ruleset_axioms" => {
                    if let Some(payload) = influence.get("payload").and_then(|v| v.as_object()) {
                        for (key, val) in payload {
                            if let Some(num) = val.as_f64() {
                                self.axioms.insert(key.clone(), num);
                            }
                        }
                    }
                },
                _ => {}
            }
        }
    }
    /// Apply effects of hyper-agents on the universe (§53.1).
    pub fn perform_deity_intervention(&mut self, zone_index: usize, trait_index: usize) {
        if let Some(z) = self.zones.get_mut(zone_index) {
            match trait_index {
                // Power traits (Dominance, Ambition, Aggression)
                0 | 1 | 2 => {
                    z.state.embodied_knowledge = (z.state.embodied_knowledge + 0.05).min(1.0);
                    z.state.entropy = (z.state.entropy + 0.02).min(1.0); // Reduced from 0.05
                },
                // Empathy/Social traits (Compassion, Altruism)
                4 | 5 => {
                    z.state.entropy = (z.state.entropy - 0.15).max(0.0); // Increased cooling
                    z.state.trauma = (z.state.trauma - 0.1).max(0.0);    // Increased healing
                },
                // Cognitive/Curiosity (Intellect)
                8 => {
                    z.state.knowledge_frontier = (z.state.knowledge_frontier + 0.08).min(z.state.tech_ceiling);
                },
                // Fear/Grief (High-distress triggers)
                11 | 14 => {
                    z.state.trauma = (z.state.trauma + 0.08).min(1.0);
                    z.state.entropy = (z.state.entropy + 0.01).min(1.0);
                },
                _ => {}
            }
            z.state.update_material_stress();
        }
    }
}

#[cfg(test)]
mod tests {
    use super::*;
    use crate::types::WorldConfig;
    /// Deity interventions must not break boundedness invariants.
    #[test]
    fn test_deity_intervention_boundedness() {
        let mut state = UniverseState::with_one_zone(1, 100.0);

        // Apply many interventions of different types in succession
        let trait_indices = [0, 1, 2, 4, 5, 8, 11, 14, 99];
        for _ in 0..50 {
            for &ti in &trait_indices {
                state.perform_deity_intervention(0, ti);
            }
        }

        let z = &state.zones[0].state;
        assert!(z.entropy >= 0.0 && z.entropy <= 1.0,
            "After 450 deity interventions: entropy={} out of [0,1]", z.entropy);
        assert!(z.trauma >= 0.0 && z.trauma <= 1.0,
            "After 450 deity interventions: trauma={} out of [0,1]", z.trauma);
        assert!(z.embodied_knowledge >= 0.0 && z.embodied_knowledge <= 1.0,
            "After 450 deity interventions: embodied_knowledge={} out of [0,1]", z.embodied_knowledge);
        assert!(z.knowledge_frontier <= z.tech_ceiling,
            "After deity interventions: frontier={} > ceiling={}", z.knowledge_frontier, z.tech_ceiling);
        assert!(z.material_stress >= 0.0 && z.material_stress <= 1.0,
            "After deity interventions: material_stress={} out of [0,1]", z.material_stress);
    }
    /// Level-8 Intelligence & Narrative: verify archetype discovery and tag generation.
    #[test]
    fn test_intelligence_and_narrative() {
        let world = WorldConfig { world_id: 1, ..Default::default() };
        let mut state = UniverseState::new(1);
        
        // Setup a Tech-heavy zone
        let mut z = ZoneState::new(100.0);
        z.knowledge_frontier = 0.8;
        z.tech_ceiling = 1.0;
        z.free_energy = 80.0;
        z.entropy = 0.1;
        
        z.civ_fields.knowledge = 0.9;
        z.civ_fields.wealth = 0.7;
        z.civ_fields.power = 0.4;
        z.civ_fields.survival = 0.4;
        z.civ_fields.meaning = 0.2;
        
        // Setup high innovation culture
        z.cultural.innovation_openness = 0.9;
        z.cultural.collective_trust = 0.8;
        
        state.zones.push(ZoneStateSerial { id: 0, state: z, neighbors: vec![] });
        
        // Test Intelligence Explosion directly
        state.apply_intelligence_explosion();
        assert!(state.zones[0].state.embodied_knowledge > 0.0, "Should have boosted embodied knowledge");
        
        // Test Discovery directly by setting global fields
        state.global_fields = crate::types::CivilizationFields {
            survival: 0.4,
            power: 0.4,
            wealth: 0.7,
            knowledge: 0.9,
            meaning: 0.2,
            ..Default::default()
        };
        state.run_archetype_discovery();
        
        // Test Narrative directly
        let culture_engine = crate::culture_engine::CultureEngine::new(0.01);
        state.narrative_tags = culture_engine.generate_narrative_tags(&state.zones);
        
        println!("Test Global Fields: {:?}", state.global_fields);
        if let Some(d) = &state.archetype_discovery {
            println!("Test Discovery: name={}, dist={}", d.name, d.distance);
        }
        let discovery = state.archetype_discovery.as_ref().expect("Should have discovery result");
        assert_eq!(discovery.name, "Technocracy");
        
        // Check Narrative Tags
        let has_renaissance = state.narrative_tags.iter().any(|t| t.slug == "renaissance_flame");
        assert!(has_renaissance, "Should have renaissance_flame tag");
        
        // Check Intelligence Explosion impact
        assert!(state.zones[0].state.embodied_knowledge > 0.0);
    }
}