use std::collections::HashMap;

pub struct SocialImpactEngine {
    pub fear_contagion: f32,
    pub trust_diffusion: f32,
}

impl SocialImpactEngine {
    pub fn new() -> Self {
        Self {
            fear_contagion: 0.15, // V7 increase
            trust_diffusion: 0.08,
        }
    }

    /// Calculate fear surge based on neighbors' fear levels
    pub fn calculate_fear_surge(
        &self, 
        neighbor_ids: &[u64], 
        neighbor_weights: &[f32], 
        fears_map: &HashMap<u64, f32>
    ) -> f32 {
        let mut surge = 0.0;
        for (i, &id) in neighbor_ids.iter().enumerate() {
            if let Some(&neighbor_fear) = fears_map.get(&id) {
                let weight = neighbor_weights.get(i).cloned().unwrap_or(1.0);
                if neighbor_fear > 0.4 {
                    surge += (neighbor_fear - 0.4) * weight * self.fear_contagion;
                }
            }
        }
        surge.min(0.4)
    }
}

pub struct EdictRegistry {
    pub modifiers: HashMap<String, f32>,
}

impl EdictRegistry {
    pub fn from_proto(edicts: &[crate::worldos::simulation::Edict]) -> Self {
        let mut modifiers = HashMap::new();
        for e in edicts {
            modifiers.insert(e.modifier_type.clone(), e.value);
        }
        Self { modifiers }
    }

    pub fn get_modifier(&self, key: &str) -> f32 {
        self.modifiers.get(key).cloned().unwrap_or(1.0)
    }
}
