use crate::types::*;

impl UniverseState {
    /// V7 §57: Quantum Overlay tick — decay superposition depth each tick.
    /// Zones in superposition gradually collapse. Observer presence accelerates collapse.
    pub fn tick_quantum_overlays(&mut self) {
        for z in &mut self.zones {
            if let Some(ref mut qo) = z.state.quantum_overlay {
                // Natural decay: superposition reduces each tick
                let decay = qo.probability_decay;
                // Observer presence accelerates collapse
                let observer_boost = qo.observer_presence * 0.05;
                qo.superposition_depth = (qo.superposition_depth - decay - observer_boost).max(0.0);

                // Observer presence decays over time (Kiến Trúc Sư rời đi)
                qo.observer_presence = (qo.observer_presence - 0.01).max(0.0);

                // Fully collapsed: remove overlay
                if qo.superposition_depth < 0.01 {
                    z.state.quantum_overlay = None;
                }
            }
        }
    }
    /// V7 §57: Observer Effect — when a zone is observed, increase observer_presence
    /// and add entropy cost. Called by Laravel via HTTP/gRPC.
    pub fn observe_zone(&mut self, zone_id: u32, entropy_cost: f64) {
        if let Some(z) = self.zones.iter_mut().find(|z| z.id == zone_id) {
            // Observation has a cost: increase zone entropy
            z.state.entropy = (z.state.entropy + entropy_cost).min(1.0);

            if let Some(ref mut qo) = z.state.quantum_overlay {
                // Boost observer_presence → accelerating collapse
                qo.observer_presence = (qo.observer_presence + 0.3).min(1.0);
            } else {
                // Zone is already collapsed, just pay entropy cost
            }
        }
    }
}
