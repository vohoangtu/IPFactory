use serde::{Serialize, Deserialize};

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Profession {
    pub id: String,
    pub name: String,
    pub min_tier: i8,
    pub motivation_profile: MotivationProfile,
}

#[derive(Debug, Clone, Serialize, Deserialize, Default)]
pub struct MotivationProfile {
    pub creation: f32,
    pub destruction: f32,
    pub order: f32,
    pub chaos: f32,
    pub self_preservation: f32,
    pub altruism: f32,
    pub physical: f32,
    pub metaphysical: f32,
}
