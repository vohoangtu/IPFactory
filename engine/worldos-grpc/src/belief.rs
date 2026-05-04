use crate::worldos::simulation::BeliefDefinition;

pub struct BeliefEngine {
    definitions: Vec<BeliefDefinition>,
}

impl BeliefEngine {
    pub fn new(definitions: Vec<BeliefDefinition>) -> Self {
        Self { definitions }
    }

    /// Update belief alignments for all actors
    pub fn update_alignments(
        &self,
        trait_matrix: &[f32], // [ActorCount * 17]
        current_alignments: &[f32], // [ActorCount * BeliefCount]
        actor_count: usize,
    ) -> Vec<f32> {
        let belief_count = self.definitions.len();
        if belief_count == 0 {
            return vec![];
        }

        let mut new_alignments = vec![0.0; actor_count * belief_count];

        for i in 0..actor_count {
            let actor_traits = &trait_matrix[i * 17..(i + 1) * 17];
            
            for (j, belief) in self.definitions.iter().enumerate() {
                let current_val = current_alignments[i * belief_count + j];
                
                // 1. Psychological Fit: How well do the actor's traits match the belief's ideal?
                let mut fit_score = 0.0;
                for k in 0..17 {
                    let weight = belief.trait_weights.get(k).cloned().unwrap_or(0.0);
                    let trait_val = actor_traits.get(k).cloned().unwrap_or(0.5);
                    
                    // Simple dot product or similarity
                    fit_score += weight * (trait_val - 0.5); 
                }
                
                // Sigmoid-like normalization for fit_score influence
                let fit_delta = (fit_score * 0.01).max(-0.05).min(0.05);
                
                // 2. Natural Decay or Strengthening
                // If fit is positive, alignment increases. If negative, it decreases.
                let updated_val = current_val + fit_delta;
                
                // TODO: Add social contagion influence here in future iterations
                
                new_alignments[i * belief_count + j] = updated_val.clamp(0.0, 1.0);
            }
        }

        new_alignments
    }
}
