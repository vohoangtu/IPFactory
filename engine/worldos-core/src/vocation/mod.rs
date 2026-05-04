use serde::{Serialize, Deserialize};
use std::collections::HashMap;

pub mod definitions;
pub mod scoring;

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct VocationState {
    pub current_vocation_id: String,
    pub motivation_scores: HashMap<String, f32>, // 8D model
    pub expertise_level: f32,
}

impl VocationState {
    pub fn new(id: &str) -> Self {
        Self {
            current_vocation_id: id.to_string(),
            motivation_scores: HashMap::new(),
            expertise_level: 0.1,
        }
    }
}
