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

    #[test]
    fn test_biome_shift() {
        let mut zone = ZoneState::new(100.0);
        zone.biome = Biome::Forest;
        zone.eco_fields = EcologicalFields {
            biomass: 0.2,
            biodiversity: 0.1, // Low biodiversity to ensure high stress
            resource_stress: 0.9,
        };
        zone.population_proxy = 1.0; // High population pressure
        zone.entropy = 0.5;

        let engine = EcologicalEngine::new(0.7);
        let event = engine.update(&mut zone);

        assert_eq!(zone.biome, Biome::Steppe);
        assert!(event.is_some());
        assert!(event.unwrap().contains("Steppe"));
    }
}
