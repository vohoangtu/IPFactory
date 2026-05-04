use serde::{Serialize, Deserialize};

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct RuleSet {
    pub id: String,
    pub tier: i8,
    pub physics: PhysicsRules,
    pub energy: EnergyRules,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct PhysicsRules {
    pub gravity: f32,
    pub entropy: bool,
    pub reality_stability: f32,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct EnergyRules {
    pub ambient_density: f32,
    pub system_type: String,
}

pub struct RuleSetEngine {
    pub active_rulesets: Vec<RuleSet>,
}

impl RuleSetEngine {
    pub fn new() -> Self {
        Self { active_rulesets: Vec::new() }
    }
    
    pub fn get_combined_gravity(&self) -> f32 {
        if self.active_rulesets.is_empty() { return 1.0; }
        
        // Example: weighted average or min/max based on tier
        self.active_rulesets.iter()
            .map(|r| r.physics.gravity)
            .sum::<f32>() / self.active_rulesets.len() as f32
    }
}
