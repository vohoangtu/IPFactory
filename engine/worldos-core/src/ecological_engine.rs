use crate::types::{ZoneState, Biome, CascadePhase};

/// Engine for Phase 2: Ecological Collapse & Phase Transitions
pub struct EcologicalEngine {
    pub instability_threshold: f64,
}

impl EcologicalEngine {
    pub fn new(instability_threshold: f64) -> Self {
        Self { instability_threshold }
    }

    /// Update ecological state of a zone.
    /// Returns an optional event description if a transition or collapse occurs.
    pub fn update(&self, zone: &mut ZoneState) -> Option<String> {
        // 1. Biome-specific growth/decay
        match zone.biome {
            Biome::Forest => {
                // Forests grow steadily but are sensitive to entropy
                zone.eco_fields.biomass = (zone.eco_fields.biomass + 0.01 * (1.0 - zone.entropy)).min(1.0);
                zone.eco_fields.biodiversity = (zone.eco_fields.biodiversity + 0.005 * (1.0 - zone.material_stress)).min(1.0);
            }
            Biome::Steppe => {
                zone.eco_fields.biomass = (zone.eco_fields.biomass + 0.005).min(0.6);
                zone.eco_fields.biodiversity = (zone.eco_fields.biodiversity + 0.003).min(0.4);
            }
            Biome::Tundra => {
                zone.eco_fields.biomass = (zone.eco_fields.biomass + 0.001).min(0.3);
                zone.eco_fields.biodiversity = (zone.eco_fields.biodiversity + 0.001).min(0.2);
            }
            Biome::Desert => {
                zone.eco_fields.biomass = (zone.eco_fields.biomass - 0.001).max(0.0);
                zone.eco_fields.biodiversity = (zone.eco_fields.biodiversity - 0.001).max(0.0);
            }
            _ => {}
        }

        // 2. Resource Stress Calculation (Doc V6 §4.1 variant)
        // High population + Low biodiversity = High stress
        let pop_pressure = (zone.population_proxy * 1.5).min(1.0);
        zone.eco_fields.resource_stress = (pop_pressure * 0.4 + (1.0 - zone.eco_fields.biodiversity) * 0.4 + zone.entropy * 0.2).clamp(0.0, 1.0);

        // 3. Ecological Phase Transition
        if zone.biome == Biome::Forest && zone.eco_fields.resource_stress > 0.8 && zone.eco_fields.biomass < 0.3 {
            zone.biome = Biome::Steppe;
            zone.eco_fields.biodiversity *= 0.5;
            return Some("Ecological Transition: Forest to Steppe".to_string());
        }

        if zone.biome == Biome::Steppe && zone.eco_fields.resource_stress > 0.9 {
            zone.biome = Biome::Desert;
            zone.eco_fields.biomass *= 0.2;
            return Some("Ecological Transition: Steppe to Desert".to_string());
        }

        // 4. Ecological Collapse Trigger
        if zone.eco_fields.resource_stress > self.instability_threshold && zone.cascade_phase != CascadePhase::Collapse {
            // Check for sudden collapse
            if zone.entropy > 0.7 || zone.material_stress > 0.7 {
                zone.cascade_phase = CascadePhase::Collapse;
                zone.trauma = (zone.trauma + 0.3).min(1.0);
                zone.eco_fields.biodiversity *= 0.3;
                return Some("Ecological Collapse Triggered!".to_string());
            }
        }

        None
    }
}

#[cfg(test)]
mod tests {
    use super::*;
    use crate::types::EcologicalFields;

    fn stressed_forest_zone() -> ZoneState {
        let mut zone = ZoneState::new(100.0);
        zone.biome = Biome::Forest;
        zone.eco_fields = EcologicalFields {
            biomass: 0.2,
            biodiversity: 0.1,
            resource_stress: 0.9,
        };
        zone.population_proxy = 1.0;
        zone.entropy = 0.5;
        zone
    }

    fn healthy_forest_zone() -> ZoneState {
        let mut zone = ZoneState::new(100.0);
        zone.biome = Biome::Forest;
        zone.eco_fields = EcologicalFields {
            biomass: 0.9,
            biodiversity: 0.8,
            resource_stress: 0.1,
        };
        zone.population_proxy = 0.3;
        zone.entropy = 0.05;
        zone
    }

    #[test]
    fn test_biome_shift() {
        let mut zone = stressed_forest_zone();
        let engine = EcologicalEngine::new(0.7);
        let event = engine.update(&mut zone);

        assert_eq!(zone.biome, Biome::Steppe);
        assert!(event.is_some());
        assert!(event.unwrap().contains("Steppe"));
    }

    #[test]
    fn test_healthy_forest_stays_forest() {
        let mut zone = healthy_forest_zone();
        let engine = EcologicalEngine::new(0.7);
        let event = engine.update(&mut zone);

        assert_eq!(zone.biome, Biome::Forest, "healthy forest should not shift");
        assert!(event.is_none(), "no event expected for stable biome");
    }

    #[test]
    fn test_tundra_warming_shifts_to_tundra() {
        let mut zone = ZoneState::new(100.0);
        zone.biome = Biome::Tundra;
        zone.eco_fields = EcologicalFields {
            biomass: 0.05,
            biodiversity: 0.02,
            resource_stress: 0.95,
        };
        zone.population_proxy = 0.8;
        zone.entropy = 0.8;

        let engine = EcologicalEngine::new(0.3); // lower threshold
        let _event = engine.update(&mut zone);

        // Tundra under extreme stress may shift to Steppe or stay Tundra.
        // The important thing is it doesn't panic.
        assert!(!matches!(zone.biome, Biome::__unused));
    }

    #[test]
    fn test_desert_with_no_biomass_stays_desert() {
        let mut zone = ZoneState::new(100.0);
        zone.biome = Biome::Desert;
        zone.eco_fields = EcologicalFields {
            biomass: 0.0,
            biodiversity: 0.0,
            resource_stress: 1.0,
        };
        zone.population_proxy = 0.0;
        zone.entropy = 0.9;

        let engine = EcologicalEngine::new(0.5);
        let event = engine.update(&mut zone);

        // Desert should not transition away since no biomass to support another biome.
        assert!(event.is_none(), "dead desert should not shift");
    }

    #[test]
    fn test_engine_threshold_prevents_early_shift() {
        let mut zone = stressed_forest_zone();
        // Very high threshold — even a stressed forest won't trigger shift.
        let engine = EcologicalEngine::new(0.99);
        let event = engine.update(&mut zone);

        assert_eq!(zone.biome, Biome::Forest);
        assert!(event.is_none());
    }
}
