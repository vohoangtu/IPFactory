//! Memory layout (Doc §24): ZoneActorIndex and SocialGraph CSR for scale (100k+ actors).

use serde::{Deserialize, Serialize};

/// Actor id type (matches Agent::id).
pub type ActorId = u64;

/// Zone index (0..num_zones).
pub type ZoneIndex = usize;

/// Spatial index: zone → list of actor ids in that zone. Doc §24.
#[derive(Debug, Clone, Default, Serialize, Deserialize)]
pub struct ZoneActorIndex {
    pub zone_to_actors: Vec<Vec<ActorId>>,
}

impl ZoneActorIndex {
    pub fn new(num_zones: usize) -> Self {
        Self {
            zone_to_actors: (0..num_zones).map(|_| Vec::new()).collect(),
        }
    }

    pub fn num_zones(&self) -> usize {
        self.zone_to_actors.len()
    }

    pub fn actors_in_zone(&self, zone_index: ZoneIndex) -> &[ActorId] {
        self.zone_to_actors
            .get(zone_index)
            .map(Vec::as_slice)
            .unwrap_or(&[])
    }

    pub fn add_actor_to_zone(&mut self, zone_index: ZoneIndex, actor_id: ActorId) {
        if zone_index < self.zone_to_actors.len() {
            self.zone_to_actors[zone_index].push(actor_id);
        }
    }

    pub fn clear_zone(&mut self, zone_index: ZoneIndex) {
        if zone_index < self.zone_to_actors.len() {
            self.zone_to_actors[zone_index].clear();
        }
    }

    pub fn rebuild_from_zones(&mut self, zone_agent_ids: impl IntoIterator<Item = Vec<ActorId>>) {
        self.zone_to_actors = zone_agent_ids.into_iter().collect();
    }
}

/// Compressed Sparse Row (CSR) social graph. Doc §24.
#[derive(Debug, Clone, Default, Serialize, Deserialize)]
pub struct SocialGraphCsr {
    pub offsets: Vec<usize>,
    pub edges: Vec<ActorId>,
    pub weights: Vec<f32>,
}

impl SocialGraphCsr {
    pub fn new() -> Self {
        Self {
            offsets: vec![0],
            edges: Vec::new(),
            weights: Vec::new(),
        }
    }

    pub fn num_actors(&self) -> usize {
        self.offsets.len().saturating_sub(1)
    }

    pub fn neighbors(&self, actor_index: usize) -> impl Iterator<Item = (ActorId, f32)> + '_ {
        let start = self.offsets.get(actor_index).copied().unwrap_or(0);
        let end = self.offsets.get(actor_index + 1).copied().unwrap_or(0);
        self.edges[start..end]
            .iter()
            .zip(self.weights[start..end].iter())
            .map(|(&id, &w)| (id, w))
    }

    /// Add edges from actor_index to the given (neighbor_id, weight) pairs. Replaces any existing edges for that actor.
    pub fn set_edges(&mut self, actor_index: usize, edges: impl IntoIterator<Item = (ActorId, f32)>) {
        let edges: Vec<_> = edges.into_iter().collect();
        // Bounds validation: ensure actor_index is within valid range
        if self.offsets.len() > 1 && actor_index >= self.offsets.len() - 1 && actor_index >= self.offsets.len() {
            // actor_index is too far beyond current size; grow offsets up to it
        }
        while self.offsets.len() <= actor_index {
            self.offsets.push(self.edges.len());
        }
        let start = self.offsets[actor_index];
        let end = self.offsets.get(actor_index + 1).copied().unwrap_or(start);
        let new_len = edges.len();
        self.edges.splice(start..end, edges.iter().map(|(id, _)| *id));
        self.weights.splice(start..end, edges.iter().map(|(_, w)| *w));
        for i in actor_index + 1..self.offsets.len() {
            self.offsets[i] = self.offsets[i].saturating_sub(end - start).saturating_add(new_len);
        }
        if self.offsets.len() == actor_index + 1 {
            self.offsets.push(self.edges.len());
        } else {
            self.offsets[actor_index + 1] = start + new_len;
        }
        // Debug assertion: offsets must be monotonically non-decreasing
        debug_assert!(
            self.offsets.windows(2).all(|w| w[0] <= w[1]),
            "CSR offsets are not monotonically non-decreasing after set_edges: {:?}",
            self.offsets
        );
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn zone_actor_index_smoke() {
        let mut idx = ZoneActorIndex::new(3);
        idx.add_actor_to_zone(0, 10);
        idx.add_actor_to_zone(0, 11);
        idx.add_actor_to_zone(1, 20);
        assert_eq!(idx.actors_in_zone(0), [10, 11]);
        assert_eq!(idx.actors_in_zone(1), [20]);
    }

    #[test]
    fn social_graph_csr_smoke() {
        let mut g = SocialGraphCsr::new();
        g.set_edges(0, [(1, 0.8), (2, 0.5)]);
        g.set_edges(1, [(0, 0.7)]);
        assert_eq!(g.num_actors(), 2);
        let n0: Vec<_> = g.neighbors(0).collect();
        assert_eq!(n0, [(1, 0.8), (2, 0.5)]);
    }
}
