use crate::types::{ActorTable, BehaviorContext};

/// Engine for the Belief System Layer
/// Manages ideologue spread and myth belief evolution.
pub struct BeliefSystemEngine {
    pub conversion_rate: f32,
}

impl BeliefSystemEngine {
    pub fn new(conversion_rate: f32) -> Self {
        Self { conversion_rate }
    }

    pub fn update(&self, table: &mut ActorTable, context: &mut BehaviorContext) {
        let count = table.ids.len();
        for rule in &context.social_rules {
            for i in 0..count {
                let zone_id = table.zone_ids[i] as usize;
                if let Some(field) = context.emotion_fields.get(zone_id) {
                    let triggered = match rule.condition {
                        crate::types::SocialCondition::FearGt(v) => field.fear > v,
                        crate::types::SocialCondition::TrustLt(v) => field.trust < v,
                        crate::types::SocialCondition::AngerGt(v) => field.anger > v,
                    };

                    if triggered {
                        match rule.action {
                            crate::types::SocialAction::SetTraitMask(mask) => {
                                table.traits_mask[i] |= mask;
                            }
                            crate::types::SocialAction::SetNode(target) => {
                                if table.current_node[i] == 0 {
                                    table.current_node[i] = target;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

/// Engine for the Power Structure Layer
/// Manages hierarchy and systemic coercion/institutional respect.
pub struct PowerStructureEngine {
    pub instability_feedback: f32,
}

impl PowerStructureEngine {
    pub fn new(instability_feedback: f32) -> Self {
        Self { instability_feedback }
    }

    pub fn apply_coercion(&self, _table: &mut ActorTable, _context: &BehaviorContext) {
        // Power structure can share the same social rules or have unique ones.
        // For simplicity, we use the same SocialRule system in the context.
        // (In future, might add a specific CoercionRule)
    }
}
