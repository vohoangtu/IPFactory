use crate::types::*;
use crate::constants;

impl UniverseState {
    /// Update agent motivation profiles based on 17D traits and RuleSet drift.
    pub fn tick_vocation_drift(&mut self) {
        let destiny_scale = self.axioms.get("destiny_gradient").cloned().unwrap_or(0.5) as f32;
        let curiosity_scale = self.axioms.get("causal_curiosity").cloned().unwrap_or(0.5) as f32;
        
        for zone in &mut self.zones {
            for agent in &mut zone.state.agents {
                // 1. Calculate "Trait Motivation" (The Natural Drift)
                // Creation influenced by Curiosity Scale
                let natural_creation = (agent.trait_vector[8] * 0.4 + agent.trait_vector[1] * 0.3 + curiosity_scale as f64 * 0.3) as f32;
                // Destruction: Vengeance (12) + Coercion (2)
                let natural_destruction = (agent.trait_vector[12] * 0.7 + agent.trait_vector[2] * 0.3) as f32;
                // Order: Dogmatism (9) + Conformity (6)
                let natural_order = (agent.trait_vector[9] * 0.6 + agent.trait_vector[6] * 0.4) as f32;
                // Chaos: RiskTolerance (10) - Dogmatism (9)
                let natural_chaos = (agent.trait_vector[10] * 0.8 + (1.0 - agent.trait_vector[9]) * 0.2) as f32;
                // Self-Preservation: Fear (11) + Pragmatism (7)
                let natural_self_pres = (agent.trait_vector[11] * 0.7 + agent.trait_vector[7] * 0.3) as f32;
                // Altruism: Empathy (4) + Solidarity (5)
                let natural_altruism = (agent.trait_vector[4] * 0.6 + agent.trait_vector[5] * 0.4) as f32;
                // Physical: Dominance (0) + Pride (15)
                let natural_physical = (agent.trait_vector[0] * 0.5 + agent.trait_vector[15] * 0.5) as f32;
                // Metaphysical influenced by Destiny Scale
                let natural_metaphysical = (agent.trait_vector[13] * 0.3 + agent.trait_vector[8] * 0.2 + destiny_scale as f64 * 0.5) as f32;

                // 2. Drift current motivation toward natural state (alpha = 0.05)
                let alpha = 0.05;
                agent.motivation_profile.creation += (natural_creation - agent.motivation_profile.creation) * alpha;
                agent.motivation_profile.destruction += (natural_destruction - agent.motivation_profile.destruction) * alpha;
                agent.motivation_profile.order += (natural_order - agent.motivation_profile.order) * alpha;
                agent.motivation_profile.chaos += (natural_chaos - agent.motivation_profile.chaos) * alpha;
                agent.motivation_profile.self_preservation += (natural_self_pres - agent.motivation_profile.self_preservation) * alpha;
                agent.motivation_profile.altruism += (natural_altruism - agent.motivation_profile.altruism) * alpha;
                agent.motivation_profile.physical += (natural_physical - agent.motivation_profile.physical) * alpha;
                agent.motivation_profile.metaphysical += (natural_metaphysical - agent.motivation_profile.metaphysical) * alpha;
            }
        }
    }
    /// Trigger Micro Mode (Crisis Window): Spawn agents deterministically (§3.2).
    pub fn trigger_micro_mode(&mut self, zone_index: usize) {
        if let Some(z) = self.zones.get_mut(zone_index) {
            // Only spawn if not already crowded
            if z.state.agents.len() < 10 {
                use crate::agent::{Agent, Archetype};
                // Deterministic spawn from entropy + material_stress
                let seed = (self.tick as f64 + z.state.material_stress * 1000.0) as u64;
                let mut traits = [0.5; 17];
                traits[0] = (seed % 10) as f64 / 10.0; // Dominance
                traits[11] = z.state.entropy; // Fear
                
                let agent = Agent::new(seed, traits, Archetype::Opportunist);
                z.state.agents.push(agent);
            }
        }
    }
    /// Resolve Micro Mode: Aggregate agent actions into Macro Deltas (§3.2, §5).
    /// Ends the crisis window and pushes an event to the chronicle.
    pub fn resolve_micro_mode(&mut self, zone_index: usize) -> Vec<String> {
        let mut events = Vec::new();
        if let Some(z) = self.zones.get_mut(zone_index) {
            if z.state.agents.is_empty() { return events; }
            
            let avg_violence: f64 = z.state.agents.iter()
                .map(|a| a.trait_vector[12]) // Vengeance
                .sum::<f64>() / z.state.agents.len() as f64;
            
            if avg_violence > 0.7 {
                z.state.trauma += 0.1;
                events.push("Violent Conflict Rooted".to_string());
            }
            
            // Garbage collect agents (§3.2)
            z.state.agents.clear();
        }
        events
    }
    /// Pressure = f(inequality, entropy, trauma, MaterialStress) (§3.2).
    pub fn pressure_at_zone(&self, zone_index: usize, macro_idx: Option<&crate::memory::ZoneActorIndex>) -> f64 {
        if zone_index >= self.zones.len() {
            return 0.0;
        }
        let z = &self.zones[zone_index].state;
        let base = (z.inequality * 0.2 + z.entropy * 0.3 + z.trauma * 0.2 + z.material_stress * 0.3).clamp(0.0, 1.0);
        let zone_id = self.zones[zone_index].id;
        let army_sum: f64 = if let Some(idx) = macro_idx {
            idx.actors_in_zone(zone_index).iter()
                .filter_map(|&ma_id| self.macro_agents.get(ma_id as usize))
                .filter(|ma| ma.agent_type == MacroAgentType::Army)
                .map(|ma| if ma.leader_id.is_some() { ma.strength * 1.5 } else { ma.strength })
                .sum()
        } else {
            self.macro_agents.iter()
                .filter(|a| a.zone_id == zone_id && a.agent_type == MacroAgentType::Army)
                .map(|a| if a.leader_id.is_some() { a.strength * 1.5 } else { a.strength })
                .sum()
        };
        (base + army_sum * constants::MACRO_ARMY_PRESSURE_COEFF).clamp(0.0, 1.0)
    }
    /// Level-8: Civilization Attractor Field — attractors pull zone civ_fields by distance decay.
    pub fn apply_attractor_fields(&mut self) {
        for attractor in &self.attractors {
            for zone in &mut self.zones {
                let distance = ((zone.id as i64 - attractor.zone_id as i64).abs() as f64) + 1.0;
                let influence = (attractor.radius / distance).powf(attractor.decay);
                zone.state.civ_fields.power += attractor.power * influence * 0.02;
                zone.state.civ_fields.wealth += attractor.wealth * influence * 0.02;
                zone.state.civ_fields.knowledge += attractor.knowledge * influence * 0.02;
                zone.state.civ_fields.meaning += attractor.meaning * influence * 0.02;
                zone.state.civ_fields.survival += attractor.survival * influence * 0.02;
                zone.state.civ_fields.clamp_mut();
            }
        }
    }
}

#[cfg(test)]
mod tests {
    use super::*;
    #[test]
    fn test_micro_mode_trigger() {
        let mut state = UniverseState::with_one_zone(1, 100.0);
        assert_eq!(state.zones[0].state.agents.len(), 0);

        state.zones[0].state.material_stress = 0.8;
        state.trigger_micro_mode(0);

        assert_eq!(state.zones[0].state.agents.len(), 1);
        assert_eq!(state.zones[0].state.agents[0].archetype, crate::agent::Archetype::Opportunist);
    }
}
