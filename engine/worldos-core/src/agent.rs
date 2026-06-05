//! Agent definitions: 17D Trait Vector, Archetypes, and Decision Logic (§3.1, §4).

use serde::{Deserialize, Serialize};

/// 17D Trait Vector (WORLDOS_V6 §3.1)
/// Indices:
/// 0: Dominance, 1: Ambition, 2: Coercion (Power)
/// 3: Loyalty, 4: Empathy, 5: Solidarity, 6: Conformity (Social)
/// 7: Pragmatism, 8: Curiosity, 9: Dogmatism, 10: RiskTolerance (Cognitive)
/// 11: Fear, 12: Vengeance, 13: Hope, 14: Grief, 15: Pride, 16: Shame (Emotional)
pub type TraitVector = [f64; 17];

// Trait index constants — canonical source of truth
pub const TRAIT_DOMINANCE: usize = 0;
pub const TRAIT_AMBITION: usize = 1;
pub const TRAIT_COERCION: usize = 2;
pub const TRAIT_LOYALTY: usize = 3;
pub const TRAIT_EMPATHY: usize = 4;
pub const TRAIT_SOLIDARITY: usize = 5;
pub const TRAIT_CONFORMITY: usize = 6;
pub const TRAIT_PRAGMATISM: usize = 7;
pub const TRAIT_CURIOSITY: usize = 8;
pub const TRAIT_DOGMATISM: usize = 9;
pub const TRAIT_RISK_TOLERANCE: usize = 10;
pub const TRAIT_FEAR: usize = 11;
pub const TRAIT_VENGEANCE: usize = 12;
pub const TRAIT_HOPE: usize = 13;
pub const TRAIT_GRIEF: usize = 14;
pub const TRAIT_PRIDE: usize = 15;
pub const TRAIT_SHAME: usize = 16;

/// Total number of trait dimensions. Derived from the highest indexed trait + 1.
/// Use this constant in slice arithmetic (`i * TRAIT_COUNT..(i+1) * TRAIT_COUNT`)
/// and loops (`for k in 0..TRAIT_COUNT`) so changing the trait set here propagates
/// safely to all consumers (see worldos-grpc/src/belief.rs).
pub const TRAIT_COUNT: usize = 17;

#[derive(Debug, Clone, Copy, PartialEq, Eq, Serialize, Deserialize)]
pub enum Archetype {
    Warlord,
    Zealot,
    Opportunist,
    Sage,
    Commoner,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Agent {
    pub id: u64,
    pub trait_vector: TraitVector,
    pub archetype: Archetype,
    /// Ring buffer for short-term memory (cap 5)
    pub memory: Vec<String>,
    #[serde(default)]
    pub vocation_id: Option<String>,
    #[serde(default)]
    pub motivation_profile: crate::vocation::definitions::MotivationProfile,
}

impl Agent {
    pub fn new(id: u64, traits: TraitVector, archetype: Archetype) -> Self {
        Self {
            id,
            trait_vector: traits,
            archetype,
            memory: Vec::with_capacity(5),
            vocation_id: None,
            motivation_profile: crate::vocation::definitions::MotivationProfile::default(),
        }
    }

    /// ActionUtility = BaseScore(Archetype, Context) + (TraitVector · ContextWeight) + Noise
    pub fn calculate_utility(
        &self,
        base_score: f64,
        context_weight: &TraitVector,
        noise: f64,
    ) -> f64 {
        let dot_product: f64 = self.trait_vector.iter()
            .zip(context_weight.iter())
            .map(|(t, w)| t * w)
            .sum();
        
        base_score + dot_product + noise
    }

    pub fn push_memory(&mut self, event: String) {
        if self.memory.len() >= 5 {
            self.memory.remove(0);
        }
        self.memory.push(event);
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_trait_vector_utility() {
        let mut traits = [0.0; 17];
        traits[0] = 1.0; // Dominance
        let agent = Agent::new(1, traits, Archetype::Warlord);

        let mut weights = [0.0; 17];
        weights[0] = 0.5; // Power context
        
        let utility = agent.calculate_utility(0.1, &weights, 0.05);
        // 0.1 (base) + (1.0 * 0.5) + 0.05 (noise) = 0.65
        assert!((utility - 0.65).abs() < 1e-6);
    }
}
