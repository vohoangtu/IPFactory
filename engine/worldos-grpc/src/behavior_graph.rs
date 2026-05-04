use std::collections::HashMap;

#[derive(Debug, Clone)]
pub struct BehaviorNode {
    pub id: i32,
    pub name: String,
    pub action_type: String,
}

#[derive(Debug, Clone)]
pub struct BehaviorTransition {
    pub from_node_id: i32,
    pub to_node_id: i32,
    pub condition: String,
    pub weight: f32,
}

#[derive(Debug, Clone)]
pub struct BehaviorGraph {
    pub archetype: String,
    pub nodes: HashMap<i32, BehaviorNode>,
    pub transitions: Vec<BehaviorTransition>,
}

pub struct BehaviorGraphEngine {
    pub graphs: HashMap<String, BehaviorGraph>,
}

impl BehaviorGraphEngine {
    pub fn new() -> Self {
        Self {
            graphs: HashMap::new(),
        }
    }

    pub fn add_graph(&mut self, graph: BehaviorGraph) {
        self.graphs.insert(graph.archetype.clone(), graph);
    }

    pub fn evaluate(
        &self,
        archetype: &str,
        current_state_id: i32,
        traits: &[f32],
        metrics: &[f32], // [hunger, energy, fear, trauma]
    ) -> i32 {
        let graph = match self.graphs.get(archetype) {
            Some(g) => g,
            None => return current_state_id,
        };

        // Find all valid transitions from current state
        let mut best_to_node = current_state_id;
        let mut max_weight = -1.0;

        for trans in &graph.transitions {
            if trans.from_node_id == current_state_id {
                if self.eval_condition(&trans.condition, traits, metrics) {
                    if trans.weight > max_weight {
                        max_weight = trans.weight;
                        best_to_node = trans.to_node_id;
                    }
                }
            }
        }

        best_to_node
    }

    fn eval_condition(&self, condition: &str, traits: &[f32], metrics: &[f32]) -> bool {
        // Simple parser for "variable > value" or "variable < value"
        let parts: Vec<&str> = condition.split_whitespace().collect();
        if parts.len() != 3 {
            return false;
        }

        let var_name = parts[0];
        let op = parts[1];
        let Ok(val) = parts[2].parse::<f32>() else { return false; };

        use worldos_core::agent::*;

        let var_val = match var_name {
            "hunger" => metrics[0],
            "energy" => metrics[1],
            "fear" => metrics[2],
            "trauma" => metrics[3],
            // 17D traits — using canonical constants from worldos_core::agent
            "dominance" => traits[TRAIT_DOMINANCE],
            "ambition" => traits[TRAIT_AMBITION],
            "coercion" => traits[TRAIT_COERCION],
            "loyalty" => traits[TRAIT_LOYALTY],
            "empathy" => traits[TRAIT_EMPATHY],
            "solidarity" => traits[TRAIT_SOLIDARITY],
            "conformity" => traits[TRAIT_CONFORMITY],
            "pragmatism" => traits[TRAIT_PRAGMATISM],
            "curiosity" => traits[TRAIT_CURIOSITY],
            "dogmatism" => traits[TRAIT_DOGMATISM],
            "risk_tolerance" => traits[TRAIT_RISK_TOLERANCE],
            "vengeance" => traits[TRAIT_VENGEANCE],
            "hope" => traits[TRAIT_HOPE],
            "grief" => traits[TRAIT_GRIEF],
            "pride" => traits[TRAIT_PRIDE],
            "shame" => traits[TRAIT_SHAME],
            _ => 0.0,
        };

        match op {
            ">" => var_val > val,
            "<" => var_val < val,
            "==" => (var_val - val).abs() < 0.01,
            ">=" => var_val >= val,
            "<=" => var_val <= val,
            _ => false,
        }
    }
}
