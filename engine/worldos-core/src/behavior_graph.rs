use crate::types::{ActorTable, EmotionField, BehaviorContext};
use smallvec::SmallVec;
use serde::{Deserialize, Serialize};

#[derive(Debug, Clone, Serialize, Deserialize)]
pub enum Condition {
    HungerGt(f32),
    FearGt(f32),
    EnergyLt(f32),
    TraitMask(u64),
    EmotionFearGt(f32),
}

impl Condition {
    pub fn check(&self, table: &ActorTable, actor_idx: usize, field: &EmotionField) -> bool {
        match self {
            Condition::HungerGt(v) => table.hunger[actor_idx] > *v,
            Condition::FearGt(v) => table.fear[actor_idx] > *v,
            Condition::EnergyLt(v) => table.energy[actor_idx] < *v,
            Condition::TraitMask(mask) => (table.traits_mask[actor_idx] & mask) != 0,
            Condition::EmotionFearGt(v) => field.fear > *v,
        }
    }
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub enum Action {
    Eat,
    StealFood,
    Flee,
}

impl Action {
    pub fn apply(&self, table: &mut ActorTable, actor_idx: usize) {
        match self {
            Action::Eat => {
                table.hunger[actor_idx] = (table.hunger[actor_idx] - 0.5).max(0.0);
            }
            Action::StealFood => {
                table.energy[actor_idx] = (table.energy[actor_idx] + 0.3).min(1.0);
            }
                        Action::Flee => {
                table.energy[actor_idx] = (table.energy[actor_idx] - 0.2).max(0.0);
            }
        }
        // Recording Intent Slug based on action
        table.intents[actor_idx] = match self {
            Action::Eat => "HUNGER_EAT".to_string(),
            Action::StealFood => "HUNGER_STEAL".to_string(),
            Action::Flee => "FEAR_ESCAPE".to_string(),
        };
    }
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Transition {
    pub condition: Condition,
    pub target: u16,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct BehaviorNode {
    pub id: u16,
    pub action: Option<Action>,
    pub transitions: SmallVec<[Transition; 4]>,
}

#[derive(Debug, Clone, Default)]
pub struct NodeBuckets {
    pub buckets: Vec<Vec<usize>>,
}

impl NodeBuckets {
    pub fn rebuild(&mut self, table: &ActorTable, node_count: usize) {
        self.buckets.clear();
        self.buckets.resize(node_count, Vec::new());
        for (i, &node_id) in table.current_node.iter().enumerate() {
            if (node_id as usize) < node_count {
                self.buckets[node_id as usize].push(i);
            }
        }
    }
}

pub struct BehaviorGraphEngine {
    pub nodes: Vec<BehaviorNode>,
    pub buckets: NodeBuckets,
}

impl BehaviorGraphEngine {
    pub fn new(nodes: Vec<BehaviorNode>) -> Self {
        Self {
            nodes,
            buckets: NodeBuckets::default(),
        }
    }

    pub fn evaluate(&mut self, table: &mut ActorTable, context: &BehaviorContext) {
        // 1. Rebuild buckets for cache-friendly iteration
        self.buckets.rebuild(table, self.nodes.len());

        // 2. Process each node's actors
        // Note: For 1M actors, use rayon here to process nodes or buckets in parallel
        for node in &self.nodes {
            let actors = &self.buckets.buckets[node.id as usize];
            if actors.is_empty() {
                continue;
            }

            for &actor_idx in actors {
                let zone_id = table.zone_ids[actor_idx] as usize;
                
                // Fallback field if zone_id is out of bounds
                let default_field = EmotionField::default();
                let field = context.emotion_fields.get(zone_id).unwrap_or(&default_field);

                // Apply action
                if let Some(action) = &node.action {
                    action.apply(table, actor_idx);
                }

                // Capture Mental State snapshot (Simplified JSON)
                table.mental_states[actor_idx] = serde_json::json!({
                    "hunger": table.hunger[actor_idx],
                    "energy": table.energy[actor_idx],
                    "fear": table.fear[actor_idx],
                    "zone_fear": field.fear
                }).to_string();

                // Check transitions
                for t in &node.transitions {
                    if t.condition.check(table, actor_idx, field) {
                        table.current_node[actor_idx] = t.target;
                        // Record transition as a potential intent change if it's the main outcome
                        if node.action.is_none() {
                            table.intents[actor_idx] = format!("TRANSITION_NODE_{}", t.target);
                        }
                        break;
                    }
                }
            }
        }
    }
}

#[cfg(test)]
mod tests {
    use super::*;
    use crate::types::EmotionField;

    #[test]
    fn test_production_behavior_engine() {
        // Setup nodes
        let idle_node = BehaviorNode {
            id: 0,
            action: None,
            transitions: smallvec::smallvec![
                Transition {
                    condition: Condition::HungerGt(0.7),
                    target: 1,
                }
            ],
        };
        let eating_node = BehaviorNode {
            id: 1,
            action: Some(Action::Eat),
            transitions: smallvec::smallvec![
                Transition {
                    condition: Condition::HungerGt(0.0), // Always transitions back if not done? 
                    // Simpler: just back to idle after one eat action
                    target: 0,
                }
            ],
        };

        let mut engine = BehaviorGraphEngine::new(vec![idle_node, eating_node]);
        let mut table = ActorTable::new();
        table.push(1, 0);
        table.hunger[0] = 0.8;

        let mut context = BehaviorContext::default();
        context.emotion_fields.push(EmotionField::default());

        // Tick 1: Transition Idle -> Eating
        engine.evaluate(&mut table, &context);
        assert_eq!(table.current_node[0], 1);

        // Tick 2: Perform Eat Action and Transition back to Idle
        engine.evaluate(&mut table, &context);
        assert_eq!(table.current_node[0], 0);
        assert!(table.hunger[0] < 0.8);
    }
}
