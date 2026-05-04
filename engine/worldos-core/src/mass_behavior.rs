use crate::types::{ActorTable, Crowd, EmotionField, BehaviorContext};

/// The Engine for the Meso Layer: Mass Behavior
pub struct MassBehaviorEngine {
    pub active_crowds: Vec<Crowd>,
}

impl MassBehaviorEngine {
    pub fn new() -> Self {
        Self {
            active_crowds: Vec::new(),
        }
    }

    /// Detects and clusters actors into crowds based on location, emotion, and dominant memes
    pub fn detect_crowds(&mut self, table: &ActorTable, context: &BehaviorContext) {
        self.active_crowds.clear();
        let actor_count = table.ids.len();
        let zones_count = context.emotion_fields.len();
        
        for zone in 0..zones_count {
            let mut crowd_size = 0;
            let mut total_fear = 0.0;
            let mut total_anger = 0.0;

            for i in 0..actor_count {
                if table.zone_ids[i] == zone as u32 {
                    if table.fear[i] > 0.5 || table.hunger[i] > 0.8 {
                        crowd_size += 1;
                        total_fear += table.fear[i];
                        total_anger += if table.hunger[i] > 0.8 { 0.5 } else { 0.0 };
                    }
                }
            }

            if crowd_size >= 10 {
                self.active_crowds.push(Crowd {
                    id: zone as u64,
                    zone_id: zone as u32,
                    size: crowd_size,
                    emotion: EmotionField {
                        fear: total_fear / (crowd_size as f32),
                        anger: total_anger / (crowd_size as f32),
                        hope: 0.0,
                        trust: 0.0,
                    },
                    dominant_meme: 0,
                });
            }
        }
    }

    /// Processes crowd behaviors and influences individual actor nodes based on rules
    pub fn apply_dynamics(&self, table: &mut ActorTable, context: &BehaviorContext) {
        let count = table.ids.len();
        for crowd in &self.active_crowds {
            for rule in &context.crowd_rules {
                if crowd.size >= rule.min_size && crowd.emotion.anger >= rule.min_anger && crowd.emotion.fear >= rule.min_fear {
                    // Apply influence to all actors in the zone
                    for i in 0..count {
                        if table.zone_ids[i] == crowd.zone_id {
                            match rule.influence {
                                crate::types::CrowdInfluence::SetNode(target_node) => {
                                    if table.current_node[i] == 0 { // Only influence if Idle
                                        table.current_node[i] = target_node;
                                    }
                                }
                                crate::types::CrowdInfluence::AddFear(v) => {
                                    table.fear[i] = (table.fear[i] + v).min(1.0);
                                }
                                crate::types::CrowdInfluence::AddAnger(v) => {
                                    // Current ActorTable doesn't have explicit 'anger', it's modeled via traits or memes
                                    // For simplicity, we could add it or use fear as a proxy for now
                                    table.fear[i] = (table.fear[i] + v).min(1.0);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
