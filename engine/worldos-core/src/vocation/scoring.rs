use super::definitions::MotivationProfile;

pub fn calculate_vocation_alignment(
    actor_motivation: &MotivationProfile,
    target_profile: &MotivationProfile
) -> f32 {
    // Basic dot product for alignment
    let mut score = 0.0;
    
    score += actor_motivation.creation * target_profile.creation;
    score += actor_motivation.destruction * target_profile.destruction;
    score += actor_motivation.order * target_profile.order;
    score += actor_motivation.chaos * target_profile.chaos;
    score += actor_motivation.self_preservation * target_profile.self_preservation;
    score += actor_motivation.altruism * target_profile.altruism;
    score += actor_motivation.physical * target_profile.physical;
    score += actor_motivation.metaphysical * target_profile.metaphysical;
    
    // Normalize if needed, or return raw alignment
    score
}
