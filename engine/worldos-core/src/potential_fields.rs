use crate::types::*;

pub struct PotentialFieldEngine {
    pub decay: Fix,
    pub diffusion_rate: Fix,
}

impl PotentialFieldEngine {
    pub fn new() -> Self {
        Self {
            decay: 0.97,            // Matching PHP PotentialFieldEngine::DECAY
            diffusion_rate: 0.1,    // Matching PHP PotentialFieldEngine::DIFFUSION_RATE
        }
    }

    pub fn update(&self, universe: &mut UniverseState) {
        let num_zones = universe.zones.len();
        if num_zones == 0 { return; }

        let mut next_pressures = vec![ZonePressures::default(); num_zones];

        // 1. Decay and Internal Delta (Simplified for now, can add complex logic later)
        for i in 0..num_zones {
            let z = &universe.zones[i];
            let current = &z.state.pressures;
            
            // Apply Decay
            next_pressures[i].war = current.war * self.decay;
            next_pressures[i].economic = current.economic * self.decay;
            next_pressures[i].religious = current.religious * self.decay;
            next_pressures[i].migration = current.migration * self.decay;
            next_pressures[i].innovation = current.innovation * self.decay;

            // Internal Deltas could be added here based on zone state (entropy, pop, etc.)
            // For now we keep it pure decay + diffusion to match legacy bridge first.
        }

        // 2. Diffusion (Phase 3 style)
        let zone_map: std::collections::HashMap<u32, usize> = universe.zones.iter().enumerate().map(|(idx, z)| (z.id, idx)).collect();

        let mut diffusion_sums = vec![ZonePressures::default(); num_zones];
        for i in 0..num_zones {
            let zone = &universe.zones[i];
            let neighbors = &zone.neighbors;
            if neighbors.is_empty() { continue; }

            for &neighbor_id in neighbors {
                if let Some(&j) = zone_map.get(&neighbor_id) {
                    let neighbor_p = &universe.zones[j].state.pressures;
                    let self_p = &universe.zones[i].state.pressures;

                    diffusion_sums[i].war += neighbor_p.war - self_p.war;
                    diffusion_sums[i].economic += neighbor_p.economic - self_p.economic;
                    diffusion_sums[i].religious += neighbor_p.religious - self_p.religious;
                    diffusion_sums[i].migration += neighbor_p.migration - self_p.migration;
                    diffusion_sums[i].innovation += neighbor_p.innovation - self_p.innovation;
                }
            }

            let n_len = neighbors.len() as f64;
            if n_len < 1e-9 { continue; }
            let next = &mut next_pressures[i];
            let diff = &diffusion_sums[i];

            next.war += (diff.war / n_len) * self.diffusion_rate;
            next.economic += (diff.economic / n_len) * self.diffusion_rate;
            next.religious += (diff.religious / n_len) * self.diffusion_rate;
            next.migration += (diff.migration / n_len) * self.diffusion_rate;
            next.innovation += (diff.innovation / n_len) * self.diffusion_rate;
        }

        // 3. Apply and Clamp
        for i in 0..num_zones {
            universe.zones[i].state.pressures = next_pressures[i].clone();
            universe.zones[i].state.pressures.clamp_mut();
        }
    }
}
