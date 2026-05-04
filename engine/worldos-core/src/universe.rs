//! Universe state: zones (SlotMap), global_entropy, knowledge_core.
//! 3-phase tick: (1) zone local update, (2) aggregate, (3) diffusion.

use std::collections::HashMap;

use crate::constants;
use crate::types::*;
use crate::systems;





impl UniverseState {
    /// Update a ghost zone's state from an external snapshot
    pub fn apply_ghost_update(&mut self, snapshot: ZoneStateSerial) {
        if let Some(ghost) = self.ghost_zones.iter_mut().find(|gz| gz.id == snapshot.id) {
            ghost.state_snapshot = snapshot;
        }
    }

    pub fn build_macro_index(&self) -> crate::memory::ZoneActorIndex {
        let num_zones = self.zones.len();
        let mut id_to_idx = HashMap::with_capacity(num_zones);
        for (idx, z) in self.zones.iter().enumerate() {
            id_to_idx.insert(z.id, idx);
        }
        let mut index = crate::memory::ZoneActorIndex::new(num_zones);
        for (ma_idx, ma) in self.macro_agents.iter().enumerate() {
            if let Some(&z_idx) = id_to_idx.get(&ma.zone_id) {
                index.add_actor_to_zone(z_idx, ma_idx as u64);
            }
        }
        index
    }



    pub fn new(universe_id: u64) -> Self {
        Self {
            universe_id,
            tick: 0,
            zones: Vec::new(),
            global_entropy: 0.0,
            knowledge_core: 0.0,
            instability_gradient: 0.0,
            sci: 1.0,
            global_fields: CivilizationFields::default(),
            scars: Vec::new(),
            attractors: Vec::new(),
            dark_attractors: Vec::new(),
            macro_agents: Vec::new(),
            actor_table: crate::types::ActorTable::default(),
            behavior_context: crate::types::BehaviorContext::default(),
            local_shard_id: 0,
            ghost_zones: Vec::new(),
            archetype_discovery: None,
            narrative_tags: Vec::new(),
            fork_recommendation: false,
            axioms: HashMap::new(),
            pending_history_events: Vec::new(),
            pending_celebrities: Vec::new(),
            pending_artifacts: Vec::new(),
        }
    }

    /// Single zone for testing / minimal run.
    pub fn with_one_zone(universe_id: u64, base_mass: Fix) -> Self {
        let mut z = ZoneState::new(base_mass);
        z.update_material_stress();
        Self {
            universe_id,
            tick: 0,
            zones: vec![ZoneStateSerial {
                id: 0,
                state: z,
                neighbors: vec![],
            }],
            global_entropy: 0.0,
            knowledge_core: 0.0,
            instability_gradient: 0.0,
            sci: 1.0,
            global_fields: CivilizationFields::default(),
            scars: Vec::new(),
            attractors: Vec::new(),
            dark_attractors: Vec::new(),
            macro_agents: Vec::new(),
            actor_table: crate::types::ActorTable::default(),
            behavior_context: crate::types::BehaviorContext::default(),
            local_shard_id: 0,
            ghost_zones: Vec::new(),
            archetype_discovery: None,
            narrative_tags: Vec::new(),
            fork_recommendation: false,
            axioms: HashMap::new(),
            pending_history_events: Vec::new(),
            pending_celebrities: Vec::new(),
            pending_artifacts: Vec::new(),
        }
    }


    /// Run one 3-phase tick (simplified: no SlotMap in this struct; we use vec for serialization).
    /// Run one 3-phase tick (simplified: no SlotMap in this struct; we use vec for serialization).
    pub fn tick(&mut self, world: &crate::types::WorldConfig, macro_idx: Option<&crate::memory::ZoneActorIndex>) {
        systems::run_all_systems(self, world, macro_idx);
    }












    pub fn to_snapshot(&self) -> UniverseSnapshot {
        let state_vector = serde_json::to_value(self).unwrap_or(serde_json::json!({}));
        let metrics = self.calculate_metrics();
        // Có drift (đã chạy ít nhất 1 tick) thì entropy không thể là 0 — sàn khi trả snapshot
        let entropy_val = if self.tick > 0 {
            self.global_entropy.max(constants::ENTROPY_DRIFT_PER_TICK)
        } else {
            self.global_entropy
        };
        UniverseSnapshot {
            universe_id: self.universe_id,
            tick: self.tick,
            state_vector,
            entropy: Some(entropy_val),
            stability_index: Some(metrics.order),
            metrics: Some(serde_json::to_value(&metrics).unwrap_or(serde_json::json!({}))),
        }
    }

    pub fn calculate_metrics(&self) -> SimulationMetrics {
        let order = 1.0 - self.global_entropy;
        let sum_v: f64 = self.zones.iter()
            .map(|z| z.state.active_materials.iter().map(|m| m.output).sum::<f64>())
            .sum();
        let energy = (1.0 + sum_v).ln();
        let ip_score = order * (1.0 - self.global_entropy / 2.0);

        SimulationMetrics {
            order,
            energy,
            ip_score,
            knowledge_core: self.knowledge_core,
            tech_ceiling_avg: self.zones.iter().map(|z| z.state.tech_ceiling).sum::<f64>() / (self.zones.len() as f64).max(1.0),
            knowledge_frontier_avg: self.zones.iter().map(|z| z.state.knowledge_frontier).sum::<f64>() / (self.zones.len() as f64).max(1.0),
            instability_gradient: self.instability_gradient,
            zone_count: self.zones.len() as u32,
            civ_fields: self.global_fields.clone(),
            scars: self.scars.clone(),
        }
    }


    pub fn merge(&mut self, other: UniverseState) {
        self.tick = self.tick.max(other.tick);
        self.global_entropy = (self.global_entropy + other.global_entropy) / 2.0;
        self.knowledge_core = self.knowledge_core.max(other.knowledge_core);

        for other_z in other.zones {
            // Check for ID collision
            let mut found_index = None;
            for (i, z) in self.zones.iter().enumerate() {
                if z.id == other_z.id {
                    found_index = Some(i);
                    break;
                }
            }

            if let Some(idx) = found_index {
                // Conflict Resolution via Material Resonance (§52.2)
                let existing_z = &mut self.zones[idx];
                Self::resolve_collision_static(existing_z, &other_z);
            } else {
                // Unique zone, simply integrate
                self.zones.push(other_z);
            }
        }
        
        self.sci = (self.sci + other.sci) / 2.0;
    }

    fn resolve_collision_static(existing: &mut ZoneStateSerial, other: &ZoneStateSerial) {
        // Higher Frontier dominates but absorbs entropy
        if other.state.knowledge_frontier > existing.state.knowledge_frontier {
            existing.state.knowledge_frontier = other.state.knowledge_frontier;
            existing.state.tech_ceiling = existing.state.tech_ceiling.max(other.state.tech_ceiling);
        }
        
        // Blend entropy and trauma
        existing.state.entropy = (existing.state.entropy + other.state.entropy) / 2.0;
        existing.state.trauma = (existing.state.trauma + other.state.trauma).min(1.0);
        
        // Merge Material Instances
        for m_other in &other.state.active_materials {
            if !existing.state.active_materials.iter().any(|m| m.slug == m_other.slug) {
                existing.state.active_materials.push(m_other.clone());
            }
        }
    }
}

#[cfg(test)]
mod tests {
    use super::*;
    use crate::types::{ActiveMaterial, PressureCoefficients, WorldConfig};


    #[test]
    fn test_material_resonance_same_slug_amplifies_effect() {
        let world = WorldConfig { world_id: 1, ..Default::default() };
        let coeff = PressureCoefficients { entropy: 0.5, order: 0.0, innovation: 0.0, growth: 0.0 };

        let mut state_one = UniverseState::with_one_zone(1, 100.0);
        state_one.zones[0].state.active_materials.push(ActiveMaterial {
            slug: "test_material".to_string(),
            output: 0.5,
            pressure_coefficients: PressureCoefficients { entropy: 0.5, order: 0.0, innovation: 0.0, growth: 0.0 },
            recursive_core: None,
        });
        let entropy_before_one = state_one.zones[0].state.entropy;
        state_one.tick(&world, None);
        let delta_one = state_one.zones[0].state.entropy - entropy_before_one;

        let mut state_two = UniverseState::with_one_zone(1, 100.0);
        state_two.zones[0].state.active_materials.push(ActiveMaterial {
            slug: "test_material".to_string(),
            output: 0.5,
            pressure_coefficients: coeff,
            recursive_core: None,
        });
        state_two.zones[0].state.active_materials.push(ActiveMaterial {
            slug: "test_material".to_string(),
            output: 0.5,
            pressure_coefficients: PressureCoefficients { entropy: 0.5, order: 0.0, innovation: 0.0, growth: 0.0 },
            recursive_core: None,
        });
        let entropy_before_two = state_two.zones[0].state.entropy;
        state_two.tick(&world, None);
        let delta_two = state_two.zones[0].state.entropy - entropy_before_two;

        assert!(delta_two > delta_one, "two same-slug materials should produce larger entropy delta (1.5x resonance)");
        assert!(delta_two >= 1.4 * delta_one, "resonance multiplier should be ~1.5x for >=2 same slug");
    }

    #[test]
    fn test_tick_determinism() {
        let world = WorldConfig { world_id: 1, ..Default::default() };
        let mut state_a = UniverseState::with_one_zone(1, 100.0);
        state_a.zones[0].state.entropy = 0.5;
        state_a.zones[0].state.knowledge_frontier = 10.0;
        
        let mut state_b = state_a.clone();
        
        state_a.tick(&world, None);
        state_b.tick(&world, None);
        
        // Assert identical outcomes
        assert_eq!(state_a.global_entropy, state_b.global_entropy, "Tick must be deterministic for entropy");
        assert_eq!(state_a.sci, state_b.sci, "Tick must be deterministic for SCI");
        assert_eq!(state_a.zones[0].state.embodied_knowledge, state_b.zones[0].state.embodied_knowledge);
    }

    #[test]
    fn test_boundedness_invariants() {
        let world = WorldConfig { world_id: 1, ..Default::default() };
        let mut state = UniverseState::with_one_zone(1, 100.0);
        
        // Inject extreme out-of-bounds values
        state.zones[0].state.entropy = 100.0; // very high
        state.zones[0].state.material_stress = -50.0; // very low
        state.zones[0].state.knowledge_frontier = 5000.0;
        state.zones[0].state.tech_ceiling = 10.0; // frontier > ceiling
        
        state.tick(&world, None);
        
        let z = &state.zones[0].state;
        assert!(z.material_stress >= 0.0 && z.material_stress <= 1.0, "Material stress must clamp 0-1");
        assert!(state.sci >= 0.0 && state.sci <= 1.0, "Global SCI must clamp 0-1");
        assert!(z.knowledge_frontier <= z.tech_ceiling, "Knowledge frontier cannot exceed tech ceiling");
        assert!(z.civ_fields.power >= 0.0 && z.civ_fields.power <= 1.0, "Power field must clamp 0-1");
        assert!(z.civ_fields.survival >= 0.0 && z.civ_fields.survival <= 1.0, "Survival field must clamp 0-1");
    }

    /// Exhaustive multi-tick boundedness: run 100 ticks with extreme initial conditions
    /// and verify that ALL state variables remain within valid bounds on every tick.
    #[test]
    fn test_exhaustive_multi_tick_boundedness() {
        let world = WorldConfig { world_id: 1, ..Default::default() };
        let mut state = UniverseState::with_one_zone(1, 100.0);

        // Start with extreme out-of-bounds values
        state.zones[0].state.entropy = 999.0;
        state.zones[0].state.material_stress = -100.0;
        state.zones[0].state.knowledge_frontier = 50000.0;
        state.zones[0].state.tech_ceiling = 10.0;
        state.zones[0].state.trauma = 50.0;
        state.zones[0].state.inequality = -20.0;

        for tick_num in 0..100 {
            state.tick(&world, None);
            let z = &state.zones[0].state;

            assert!(z.material_stress >= 0.0 && z.material_stress <= 1.0,
                "Tick {}: material_stress={} out of [0,1]", tick_num, z.material_stress);
            assert!(z.entropy >= 0.0,
                "Tick {}: entropy={} must be >= 0", tick_num, z.entropy);
            assert!(state.sci >= 0.0 && state.sci <= 1.0,
                "Tick {}: SCI={} out of [0,1]", tick_num, state.sci);
            assert!(z.knowledge_frontier <= z.tech_ceiling,
                "Tick {}: frontier={} > ceiling={}", tick_num, z.knowledge_frontier, z.tech_ceiling);
            assert!(z.civ_fields.power >= 0.0 && z.civ_fields.power <= 1.0,
                "Tick {}: power={} out of [0,1]", tick_num, z.civ_fields.power);
            assert!(z.civ_fields.survival >= 0.0 && z.civ_fields.survival <= 1.0,
                "Tick {}: survival={} out of [0,1]", tick_num, z.civ_fields.survival);
            assert!(z.civ_fields.knowledge >= 0.0 && z.civ_fields.knowledge <= 1.0,
                "Tick {}: knowledge={} out of [0,1]", tick_num, z.civ_fields.knowledge);
            assert!(z.civ_fields.meaning >= 0.0 && z.civ_fields.meaning <= 1.0,
                "Tick {}: meaning={} out of [0,1]", tick_num, z.civ_fields.meaning);
        }
    }

    /// Snapshot reproducibility: two identical states must produce byte-identical snapshots.
    #[test]
    fn test_snapshot_reproducibility() {
        let world = WorldConfig { world_id: 1, ..Default::default() };

        let mut state_a = UniverseState::with_one_zone(1, 100.0);
        state_a.zones[0].state.entropy = 0.6;
        state_a.zones[0].state.knowledge_frontier = 5.0;
        let mut state_b = state_a.clone();

        // Run both for 10 ticks
        for _ in 0..10 {
            state_a.tick(&world, None);
            state_b.tick(&world, None);
        }

        let snap_a = state_a.to_snapshot();
        let snap_b = state_b.to_snapshot();

        // Serialise and compare byte-for-byte
        let json_a = serde_json::to_string(&snap_a).unwrap();
        let json_b = serde_json::to_string(&snap_b).unwrap();
        assert_eq!(json_a, json_b, "Identical initial states must produce identical snapshots after N ticks");

        // Also verify individual fields
        assert_eq!(snap_a.tick, snap_b.tick);
        assert_eq!(snap_a.entropy, snap_b.entropy);
        assert_eq!(snap_a.stability_index, snap_b.stability_index);
    }

    /// Multi-zone determinism: a universe with 3 zones must be deterministic.
    #[test]
    fn test_multi_zone_determinism() {
        let world = WorldConfig { world_id: 1, ..Default::default() };

        let build = || {
            let mut s = UniverseState::new(1);
            for i in 0..3 {
                let mut z = ZoneState::new(100.0);
                z.entropy = 0.3 + i as f64 * 0.1;
                z.knowledge_frontier = 5.0;
                z.update_material_stress();
                s.zones.push(ZoneStateSerial { id: i, state: z, neighbors: vec![] });
            }
            // Set neighbors for diffusion
            s.zones[0].neighbors = vec![1];
            s.zones[1].neighbors = vec![0, 2];
            s.zones[2].neighbors = vec![1];
            s
        };

        let mut a = build();
        let mut b = build();

        for _ in 0..20 {
            a.tick(&world, None);
            b.tick(&world, None);
        }

        assert_eq!(a.global_entropy, b.global_entropy, "Multi-zone global_entropy must be deterministic");
        assert_eq!(a.sci, b.sci, "Multi-zone SCI must be deterministic");
        for i in 0..3 {
            assert_eq!(a.zones[i].state.entropy, b.zones[i].state.entropy,
                "Zone {} entropy mismatch", i);
            assert_eq!(a.zones[i].state.knowledge_frontier, b.zones[i].state.knowledge_frontier,
                "Zone {} knowledge_frontier mismatch", i);
        }
    }




    /// Trade flow (Deep Sim Phase C): wealth_proxy flows from higher to lower between neighbors.
    #[test]
    fn test_trade_flow_redistributes_wealth() {
        let world = WorldConfig { world_id: 1, ..Default::default() };
        let mut state = UniverseState::new(1);
        let mut z0 = ZoneState::new(100.0);
        z0.wealth_proxy = 0.8;
        z0.entropy = 0.3;
        let mut z1 = ZoneState::new(100.0);
        z1.wealth_proxy = 0.2;
        z1.entropy = 0.4;
        state.zones.push(ZoneStateSerial { id: 0, state: z0, neighbors: vec![1] });
        state.zones.push(ZoneStateSerial { id: 1, state: z1, neighbors: vec![0] });

        let w0_before = state.zones[0].state.wealth_proxy;
        let w1_before = state.zones[1].state.wealth_proxy;
        state.tick(&world, None);
        let w0_after = state.zones[0].state.wealth_proxy;
        let w1_after = state.zones[1].state.wealth_proxy;

        assert!(w0_after < w0_before, "wealth should flow out of richer zone 0");
        assert!(w1_after > w1_before, "wealth should flow into poorer zone 1");
        assert!((w0_after + w1_after - (w0_before + w1_before)).abs() < 0.001, "total wealth approximately conserved");
    }

    /// Ghost Zone Diffusion: local zone should diffuse with ghost zone.
    #[test]
    fn test_ghost_zone_diffusion() {
        use crate::sharding::GhostZone;
        let world = WorldConfig { world_id: 1, ..Default::default() };
        let mut state = UniverseState::new(1);
        
        let mut z_local = ZoneState::new(100.0);
        z_local.entropy = 0.2;
        state.zones.push(ZoneStateSerial { id: 0, state: z_local, neighbors: vec![99] });
        
        let mut z_ghost = ZoneState::new(100.0);
        z_ghost.entropy = 0.8;
        let gz = GhostZone {
            id: 99,
            shard_id: 2,
            state_snapshot: ZoneStateSerial { id: 99, state: z_ghost, neighbors: vec![0] },
        };
        state.ghost_zones.push(gz);

        let entropy_before = state.zones[0].state.entropy;
        state.tick(&world, None);
        let entropy_after = state.zones[0].state.entropy;

        assert!(entropy_after > entropy_before, "entropy should flow from ghost (0.8) to local (0.2)");
    }




}
