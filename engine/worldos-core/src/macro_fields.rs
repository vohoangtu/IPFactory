//! Civilization Field Theory (CFT) Implementation in Rust.
//! Ported from PHP CivilizationFieldTheoryEngine for performance and architectural purity.
//! Equation: F_i(t+1) = prev + tanh(Signal + Interaction) * 0.1 + Noise

use crate::types::{UniverseState, CivilizationFields, Fix};

pub struct MacroFieldEngine {
    pub evolution_factor: Fix,
}

impl MacroFieldEngine {
    pub fn new() -> Self {
        Self {
            evolution_factor: 0.1,
        }
    }

    /// Update macro fields for the entire universe.
    pub fn update(&self, state: &mut UniverseState) {
        let prev_fields = state.global_fields.clone();
        let signals = self.compute_signals(state);
        
        // In a real implementation, we would have a CouplingMatrix. 
        // For now, we port the core logic from PHP.
        
        let mut next_fields = prev_fields.clone();
        
        // Survival
        next_fields.survival = self.evolve(prev_fields.survival, signals.survival, 0.0, 0.0);
        next_fields.power = self.evolve(prev_fields.power, signals.power, 0.0, 0.0);
        next_fields.wealth = self.evolve(prev_fields.wealth, signals.wealth, 0.0, 0.0);
        next_fields.knowledge = self.evolve(prev_fields.knowledge, signals.knowledge, 0.0, 0.0);
        next_fields.meaning = self.evolve(prev_fields.meaning, signals.meaning, 0.0, 0.0);
        
        next_fields.authority = self.evolve(prev_fields.authority, signals.authority, 0.0, 0.0);
        next_fields.fear_macro = self.evolve(prev_fields.fear_macro, signals.fear_macro, 0.0, 0.0);
        next_fields.order_macro = self.evolve(prev_fields.order_macro, signals.order_macro, 0.0, 0.0);
        next_fields.entropy_macro = self.evolve(prev_fields.entropy_macro, signals.entropy_macro, 0.0, 0.0);
        next_fields.resonance = self.evolve(prev_fields.resonance, signals.resonance, 0.0, 0.0);

        next_fields.clamp_mut();
        state.global_fields = next_fields;
    }

    fn evolve(&self, prev: Fix, signal: Fix, interaction: Fix, noise: Fix) -> Fix {
        let delta = (signal - prev + interaction).tanh();
        prev + (delta * self.evolution_factor) + noise
    }

    fn compute_signals(&self, state: &UniverseState) -> CivilizationFields {
        let n = state.zones.len() as f64;
        if n < 1.0 {
            return CivilizationFields::default();
        }

        // Aggregate metrics from zones
        let avg_pop: f64 = state.zones.iter().map(|z| z.state.population_proxy).sum::<f64>() / n;
        let avg_entropy: f64 = state.zones.iter().map(|z| z.state.entropy).sum::<f64>() / n;
        let avg_stress: f64 = state.zones.iter().map(|z| z.state.material_stress).sum::<f64>() / n;
        let avg_wealth: f64 = state.zones.iter().map(|z| z.state.wealth_proxy).sum::<f64>() / n;
        
        // Resonance & Order are macro-emergent
        let resonance = state.global_fields.resonance;
        
        CivilizationFields {
            survival: (0.5 * (1.0 - avg_stress + 0.5 * avg_pop)).clamp(0.0, 1.0),
            power: (0.7 * avg_pop + 0.3 * state.knowledge_core).clamp(0.0, 1.0),
            wealth: avg_wealth.clamp(0.0, 1.0),
            knowledge: state.knowledge_core.clamp(0.0, 1.0),
            meaning: (0.6 * (1.0 - avg_entropy + 0.4 * resonance)).clamp(0.0, 1.0),
            
            authority: (0.5 * state.global_fields.power + 0.5 * state.global_fields.order_macro).clamp(0.0, 1.0),
            fear_macro: (0.8 * avg_stress + 0.2 * avg_entropy).clamp(0.0, 1.0),
            order_macro: (1.0 - avg_entropy).clamp(0.0, 1.0),
            entropy_macro: avg_entropy.clamp(0.0, 1.0),
            resonance: (0.5 * state.global_fields.meaning + 0.5 * (1.0 - avg_entropy)).clamp(0.0, 1.0),
            
            reproduction: 0.5, // placeholders for now
            status: 0.5,
            belonging: 0.5,
        }
    }
}
