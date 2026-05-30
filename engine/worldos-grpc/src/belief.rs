use crate::worldos::simulation::BeliefDefinition;

/// Social contagion configuration for belief propagation.
pub struct ContagionConfig {
    /// Strength of peer influence (0.0 = no contagion, 0.3 = strong).
    pub influence_strength: f32,
    /// Minimum trait similarity (0.0–1.0) required for contagion to apply.
    pub similarity_threshold: f32,
}

impl Default for ContagionConfig {
    fn default() -> Self {
        Self {
            influence_strength: 0.1,
            similarity_threshold: 0.2,
        }
    }
}

pub struct BeliefEngine {
    definitions: Vec<BeliefDefinition>,
}

impl BeliefEngine {
    pub fn new(definitions: Vec<BeliefDefinition>) -> Self {
        Self { definitions }
    }

    /// Update belief alignments for all actors with social contagion.
    ///
    /// For each actor-belief pair, the alignment is updated based on:
    /// 1. **Psychological fit** — how well the actor's traits match the belief's ideal weights.
    /// 2. **Social contagion** — peer influence from actors with similar trait profiles.
    pub fn update_alignments(
        &self,
        trait_matrix: &[f32],        // [ActorCount * 17]
        current_alignments: &[f32],  // [ActorCount * BeliefCount]
        actor_count: usize,
    ) -> Vec<f32> {
        self.update_alignments_with_contagion(
            trait_matrix,
            current_alignments,
            actor_count,
            &ContagionConfig::default(),
        )
    }

    /// Full alignment update including social contagion influence.
    ///
    /// Social contagion works by:
    /// - Computing pairwise trait similarity between actors.
    /// - For each actor, averaging the belief alignments of peers whose similarity
    ///   exceeds `config.similarity_threshold`.
    /// - Pulling the actor's alignment toward the peer average by `config.influence_strength`.
    pub fn update_alignments_with_contagion(
        &self,
        trait_matrix: &[f32],
        current_alignments: &[f32],
        actor_count: usize,
        config: &ContagionConfig,
    ) -> Vec<f32> {
        let belief_count = self.definitions.len();
        if belief_count == 0 {
            return vec![];
        }

        // Precompute peer influence for each belief.
        let contagion_deltas = self.compute_contagion_deltas(
            trait_matrix,
            current_alignments,
            actor_count,
            belief_count,
            config,
        );

        let mut new_alignments = vec![0.0; actor_count * belief_count];

        for i in 0..actor_count {
            let actor_traits = &trait_matrix[i * 17..(i + 1) * 17];

            for (j, belief) in self.definitions.iter().enumerate() {
                let current_val = current_alignments[i * belief_count + j];

                // 1. Psychological Fit
                let mut fit_score = 0.0;
                for k in 0..17 {
                    let weight = belief.trait_weights.get(k).cloned().unwrap_or(0.0);
                    let trait_val = actor_traits.get(k).cloned().unwrap_or(0.5);
                    fit_score += weight * (trait_val - 0.5);
                }
                let fit_delta = (fit_score * 0.01).clamp(-0.05, 0.05);
                let updated_val = current_val + fit_delta;

                // 2. Social Contagion — pull toward peer average alignment.
                let contagion_idx = i * belief_count + j;
                let contagion_delta = contagion_deltas[contagion_idx];
                let final_val = (updated_val + contagion_delta).clamp(0.0, 1.0);

                new_alignments[i * belief_count + j] = final_val;
            }
        }

        new_alignments
    }

    /// Compute social contagion deltas: how much each actor's alignment is pulled
    /// toward the average of similar peers.
    fn compute_contagion_deltas(
        &self,
        trait_matrix: &[f32],
        current_alignments: &[f32],
        actor_count: usize,
        belief_count: usize,
        config: &ContagionConfig,
    ) -> Vec<f32> {
        let mut deltas = vec![0.0; actor_count * belief_count];

        if actor_count <= 1 || config.influence_strength <= 0.0 {
            return deltas;
        }

        // Precompute pairwise trait similarity.
        let similarities = self.compute_pairwise_similarities(trait_matrix, actor_count);

        for i in 0..actor_count {
            for j in 0..belief_count {
                let my_alignment = current_alignments[i * belief_count + j];

                // Compute weighted average alignment of similar peers.
                let mut peer_sum = 0.0f32;
                let mut peer_weight = 0.0f32;

                for other in 0..actor_count {
                    if other == i {
                        continue;
                    }
                    let sim = similarities[i * actor_count + other];
                    if sim < config.similarity_threshold {
                        continue;
                    }
                    let other_alignment = current_alignments[other * belief_count + j];
                    peer_sum += sim * other_alignment;
                    peer_weight += sim;
                }

                if peer_weight > 0.0 {
                    let peer_average = peer_sum / peer_weight;
                    let pull = (peer_average - my_alignment) * config.influence_strength;
                    let clamped_pull = pull.clamp(-0.05, 0.05); // Limit per-tick change.
                    deltas[i * belief_count + j] = clamped_pull;
                }
            }
        }

        deltas
    }

    /// Compute pairwise trait similarity (cosine-like) between all actors.
    fn compute_pairwise_similarities(
        &self,
        trait_matrix: &[f32],
        actor_count: usize,
    ) -> Vec<f32> {
        let mut similarities = vec![0.0; actor_count * actor_count];

        for i in 0..actor_count {
            let traits_i = &trait_matrix[i * 17..(i + 1) * 17];
            for other in 0..actor_count {
                if other == i {
                    similarities[i * actor_count + other] = 1.0;
                    continue;
                }
                let traits_other = &trait_matrix[other * 17..(other + 1) * 17];

                // Cosine-like similarity bounded to [0, 1].
                let mut dot = 0.0f32;
                let mut norm_i = 0.0f32;
                let mut norm_o = 0.0f32;
                for k in 0..17 {
                    let a = traits_i.get(k).cloned().unwrap_or(0.5);
                    let b = traits_other.get(k).cloned().unwrap_or(0.5);
                    dot += a * b;
                    norm_i += a * a;
                    norm_o += b * b;
                }
                let denom = (norm_i.sqrt() * norm_o.sqrt()).max(1e-6);
                let sim = (dot / denom).max(0.0);
                similarities[i * actor_count + other] = sim;
            }
        }

        similarities
    }
}
