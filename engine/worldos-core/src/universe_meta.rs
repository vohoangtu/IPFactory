use crate::types::*;

impl UniverseState {
    pub fn run_archetype_discovery(&mut self) {
        let archetypes = Self::get_standard_archetypes();
        let current = &self.global_fields;
        
        let mut best_name = "Fragmented".to_string();
        let mut best_dist = f64::MAX;

        for arch in archetypes {
            let dist = (
                (arch.survival - current.survival).powi(2) +
                (arch.reproduction - current.reproduction).powi(2) +
                (arch.wealth - current.wealth).powi(2) +
                (arch.power - current.power).powi(2) +
                (arch.knowledge - current.knowledge).powi(2) +
                (arch.meaning - current.meaning).powi(2) +
                (arch.status - current.status).powi(2) +
                (arch.belonging - current.belonging).powi(2)
            ).sqrt();
            if dist < best_dist {
                best_dist = dist;
                best_name = arch.name;
            }
        }

        let is_novel = best_dist > 0.35;
        self.archetype_discovery = Some(DiscoveryResult {
            name: best_name,
            distance: best_dist,
            is_novel,
        });

        // Recommend Fork if system is highly unstable or highly novel
        self.fork_recommendation = is_novel || self.instability_gradient > 0.7;
    }
    pub fn get_standard_archetypes() -> Vec<ArchetypeProfile> {
        vec![
            ArchetypeProfile { name: "Hegemon".into(), survival: 0.8, reproduction: 0.7, wealth: 0.4, power: 0.9, knowledge: 0.3, meaning: 0.5, status: 0.8, belonging: 0.7 },
            ArchetypeProfile { name: "Merchant Republic".into(), survival: 0.5, reproduction: 0.6, wealth: 0.9, power: 0.6, knowledge: 0.7, meaning: 0.4, status: 0.7, belonging: 0.6 },
            ArchetypeProfile { name: "Technocracy".into(), survival: 0.4, reproduction: 0.5, wealth: 0.7, power: 0.5, knowledge: 0.9, meaning: 0.3, status: 0.6, belonging: 0.5 },
            ArchetypeProfile { name: "Theocracy".into(), survival: 0.7, reproduction: 0.8, wealth: 0.3, power: 0.8, knowledge: 0.4, meaning: 0.9, status: 0.7, belonging: 0.8 },
            ArchetypeProfile { name: "Utopia".into(), survival: 0.8, reproduction: 0.9, wealth: 0.8, power: 0.4, knowledge: 0.8, meaning: 0.8, status: 0.7, belonging: 0.9 },
            ArchetypeProfile { name: "Survivalist".into(), survival: 0.9, reproduction: 0.8, wealth: 0.2, power: 0.3, knowledge: 0.2, meaning: 0.4, status: 0.5, belonging: 0.6 },
        ]
    }
    /// Level-8: Dark Attractor — high entropy/trauma/inequality pulls zone toward collapse.
    pub fn apply_dark_attractors(&mut self) {
        for attractor in &self.dark_attractors {
            for z in &mut self.zones {
                let e_ratio = z.state.entropy / (attractor.entropy_threshold + 1e-6);
                let t_ratio = z.state.trauma / (attractor.trauma_threshold + 1e-6);
                let i_ratio = z.state.inequality / (attractor.inequality_threshold + 1e-6);
                let risk = ((e_ratio + t_ratio + i_ratio) / 3.0).min(2.0);
                if risk > 1.0 {
                    let pull = attractor.pull_strength * risk * 0.02;
                    z.state.entropy = (z.state.entropy + pull * 0.05).min(1.0);
                    z.state.trauma = (z.state.trauma + pull * 0.03).min(1.0);
                    z.state.cultural.collective_trust =
                        (z.state.cultural.collective_trust - pull * 0.04).max(0.0);
                    z.state.cultural.clamp_mut();
                }
                if risk > 1.5 {
                    z.state.active_materials.clear();
                    z.state.structured_mass *= 0.95;
                    z.state.trauma = (z.state.trauma + 0.1).min(1.0);
                }
            }
        }
    }
    /// Level-8: Intelligence Explosion — high knowledge/energy/openness boosts growth.
    pub fn apply_intelligence_explosion(&mut self) {
        for z in &mut self.zones {
            let score = z.state.knowledge_frontier * 0.4
                + (z.state.free_energy / (z.state.base_mass + 1e-6)).min(1.0) * 0.3
                + z.state.cultural.innovation_openness * 0.3
                - z.state.entropy * 0.3;
            if score > 0.6 {
                let boost = score * 0.02;
                z.state.embodied_knowledge = (z.state.embodied_knowledge + boost).min(1.0);
                z.state.knowledge_frontier = (z.state.knowledge_frontier + boost).min(z.state.tech_ceiling);
                z.state.tech_ceiling = (z.state.tech_ceiling + boost * 0.5).min(1.0);
                z.state.free_energy += boost * z.state.base_mass * 0.1;

                // Feedback Loop: High intelligence organizes the system, reducing local entropy
                z.state.entropy = (z.state.entropy - boost * 0.5).max(0.0);
            }
        }
    }
    /// Level-8: Phase Transition — advance zone phase when thresholds are met.
    pub fn check_phase_transition(zone: &mut ZoneState) {
        use crate::types::CivilizationPhase;
        match zone.phase {
            CivilizationPhase::Tribal => {
                if zone.structured_mass > 50.0 {
                    zone.phase = CivilizationPhase::Agrarian;
                }
            }
            CivilizationPhase::Agrarian => {
                if zone.knowledge_frontier > 0.2 {
                    zone.phase = CivilizationPhase::Kingdom;
                }
            }
            CivilizationPhase::Kingdom => {
                if zone.embodied_knowledge > 0.4 {
                    zone.phase = CivilizationPhase::Empire;
                }
            }
            CivilizationPhase::Empire => {
                let energy_ratio = (zone.free_energy / (zone.base_mass + 1e-6)).min(1.0);
                if energy_ratio > 0.6 {
                    zone.phase = CivilizationPhase::Industrial;
                }
            }
            CivilizationPhase::Industrial => {
                if zone.knowledge_frontier > 0.8 {
                    zone.phase = CivilizationPhase::Information;
                }
            }
            CivilizationPhase::Information => {}
        }
    }
    /// Level-8: Possibility Space Navigator — run horizon ticks on clones, return future outcomes.
    pub fn explore_futures(
        &self,
        world: &crate::types::WorldConfig,
        horizon: u32,
        num_branches: usize,
    ) -> Vec<crate::types::FutureOutcome> {
        let mut futures = Vec::with_capacity(num_branches);
        for _ in 0..num_branches {
            let mut sim = self.clone();
            let idx = sim.build_macro_index();
            for _ in 0..horizon {
                sim.tick(world, Some(&idx));
            }
            futures.push(crate::types::FutureOutcome {
                entropy: sim.global_entropy,
                knowledge: sim.knowledge_core,
                sci: sim.sci,
                tick: sim.tick,
            });
        }
        futures
    }
    /// Check for Meta-Cycle trigger (§4.3): Major collapse when SCI is too low.
    pub fn check_meta_cycle(&mut self) -> bool {
        if self.sci < 0.3 {
            self.global_entropy = (self.global_entropy + 0.3).min(1.0);
            for z in &mut self.zones {
                z.state.entropy = (z.state.entropy + 0.5).min(1.0);
                z.state.active_materials.clear(); 
            }
            self.scars.push(StructuredScar {
                tick: self.tick,
                category: "meta_cycle".to_string(),
                description: format!("Phát động Meta-Cycle tại tick {}", self.tick),
                actor_id: None,
                zone_id: None,
                caused_by_id: None,
                metadata: serde_json::json!({ "tick": self.tick }),
            });
            return true;
        }
        false
    }
}

#[cfg(test)]
mod tests {
    use super::*;
    use crate::types::{WorldConfig, CivilizationPhase, CivilizationAttractor, DarkAttractor};
    /// Level-8: Possibility Space Navigator returns one outcome per branch, with valid metrics.
    #[test]
    fn test_explore_futures() {
        let world = WorldConfig { world_id: 1, ..Default::default() };
        let state = UniverseState::with_one_zone(1, 100.0);
        let horizon = 10u32;
        let num_branches = 5;
        let futures = state.explore_futures(&world, horizon, num_branches);
        assert_eq!(futures.len(), num_branches);
        for (i, f) in futures.iter().enumerate() {
            assert!(f.entropy >= 0.0 && f.entropy <= 1.0, "branch {} entropy {}", i, f.entropy);
            assert!(f.knowledge >= 0.0 && f.knowledge <= 1.0, "branch {} knowledge {}", i, f.knowledge);
            assert!(f.sci >= 0.0 && f.sci <= 1.0, "branch {} sci {}", i, f.sci);
            assert_eq!(f.tick, state.tick + horizon as u64);
        }
    }
    /// Level-8: Attractor, Dark Attractor, Intelligence Explosion, Phase Transition keep invariants.
    #[test]
    fn test_level8_engines_boundedness() {
        use crate::types::{CivilizationAttractor, DarkAttractor, CivilizationPhase};
        let world = WorldConfig { world_id: 1, ..Default::default() };
        let mut state = UniverseState::with_one_zone(1, 100.0);
        state.attractors.push(CivilizationAttractor {
            id: 1,
            zone_id: 0,
            power: 0.5,
            wealth: 0.3,
            knowledge: 0.2,
            meaning: 0.1,
            survival: 0.4,
            radius: 10.0,
            decay: 1.0,
        });
        state.dark_attractors.push(DarkAttractor {
            id: 1,
            entropy_threshold: 0.5,
            trauma_threshold: 0.5,
            inequality_threshold: 0.5,
            pull_strength: 0.3,
            collapse_probability: 0.2,
        });
        for _ in 0..50 {
            state.tick(&world, None);
        }
        let z = &state.zones[0].state;
        assert!(z.civ_fields.power >= 0.0 && z.civ_fields.power <= 1.0);
        assert!(z.civ_fields.survival >= 0.0 && z.civ_fields.survival <= 1.0);
        assert!(z.entropy >= 0.0 && z.entropy <= 1.0);
        assert!(z.trauma >= 0.0 && z.trauma <= 1.0);
        assert!(z.knowledge_frontier <= z.tech_ceiling);
        assert!(matches!(z.phase, CivilizationPhase::Tribal | CivilizationPhase::Agrarian | CivilizationPhase::Kingdom | CivilizationPhase::Empire | CivilizationPhase::Industrial | CivilizationPhase::Information));
    }
}
