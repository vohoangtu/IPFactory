use crate::worldos::simulation::TechnologyDefinition;

pub struct TechnologyEngine {
    definitions: Vec<TechnologyDefinition>,
}

impl TechnologyEngine {
    pub fn new(definitions: Vec<TechnologyDefinition>) -> Self {
        Self { definitions }
    }

    /// Process technology discovery and propagation
    pub fn process_step(
        &self,
        actor_count: usize,
        trait_matrix: &[f32], // [ActorCount * 17]
        current_tech_levels: &[f32], // [ActorCount * TechCount]
        belief_alignments: &[f32], // [ActorCount * BeliefCount]
        social_graph: &[crate::worldos::simulation::SocialEdge],
    ) -> Vec<f32> {
        let tech_count = self.definitions.len();
        if tech_count == 0 {
            return vec![];
        }

        let mut new_tech_levels = current_tech_levels.to_vec();

        // 1. Discovery Phase
        for i in 0..actor_count {
            let logic = trait_matrix[i * 17 + 10]; // Logic trait
            let intelligence = trait_matrix[i * 17 + 1]; // Intelligence trait (Ambition/Intellect)
            
            for (j, _tech) in self.definitions.iter().enumerate() {
                let current_lv = new_tech_levels[i * tech_count + j];
                if current_lv >= 1.0 { continue; }

                // Check requirements (simplified for now: just level of others)
                // In future, check tech codes in requirements array
                
                // Base discovery probability
                let discovery_prob = (logic * 0.1 + intelligence * 0.05) * 0.01;
                
                // Boost from specific beliefs (e.g. Technocracy)
                // Assume belief 1 is Technocracy (from seeder)
                let belief_boost = if belief_alignments.len() > i * 1 {
                    belief_alignments[i * 1] * 0.2 // Max +20% probability
                } else { 0.0 };

                if rand::random::<f32>() < (discovery_prob + belief_boost) {
                    new_tech_levels[i * tech_count + j] = (current_lv + 0.1).min(1.0);
                }
            }
        }

        // 2. Propagation Phase (Knowledge Spreads through Social Graph)
        for edge in social_graph {
            let src = edge.source_id as usize;
            let dst = edge.target_id as usize;
            
            if src < actor_count && dst < actor_count {
                for j in 0..tech_count {
                    let src_lv = new_tech_levels[src * tech_count + j];
                    let dst_lv = new_tech_levels[dst * tech_count + j];
                    
                    if src_lv > dst_lv {
                        // Spread rate proportional to social weight
                        let spread = (src_lv - dst_lv) * edge.weight * 0.05;
                        new_tech_levels[dst * tech_count + j] = (dst_lv + spread).min(1.0);
                    }
                }
            }
        }

        new_tech_levels
    }

    /// Calculate aggregate effects of technology on actor parameters
    pub fn get_metabolism_multiplier(&self, actor_index: usize, tech_levels: &[f32], tech_count: usize) -> f32 {
        let mut multiplier = 1.0;
        for (j, tech) in self.definitions.iter().enumerate() {
            let lv = tech_levels[actor_index * tech_count + j];
            if lv > 0.1 {
                // Simplified: search for "metabolism_bonus" in effects_json
                if tech.effects_json.contains("metabolism_bonus") {
                    multiplier -= lv * 0.05; // Max 5% reduction per tech
                }
            }
        }
        multiplier.max(0.1)
    }
}
