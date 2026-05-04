use crate::worldos::simulation::EmergentCalamity;
use rand::Rng;

pub struct CalamityEngine {
    pub pandemic_threshold: f32,
    pub war_threshold: f32,
    pub ecological_collapse_threshold: f32,
    pub base_trigger_chance: f64,
}

impl CalamityEngine {
    pub fn new() -> Self {
        Self {
            pandemic_threshold: 0.8,
            war_threshold: 0.9,
            ecological_collapse_threshold: 0.85,
            base_trigger_chance: 0.05,
        }
    }

    pub fn assess_risks(
        &self,
        tick: u64,
        global_entropy: f32,
        max_urban_density: f32,
        max_extraction_rate: f32,
        min_cohesion: f32,
        peak_tech_level: f32,
    ) -> Vec<EmergentCalamity> {
        let mut calamities = Vec::new();
        let mut rng = rand::thread_rng();

        // Avoid triggering calamites too often (e.g., only check every 100 ticks)
        if tick % 100 != 0 {
            return calamities;
        }

        let density_risk = max_urban_density * (1.0 + global_entropy * 0.5);
        let war_risk = (1.0 - min_cohesion) * (1.0 + peak_tech_level);
        let eco_risk = max_extraction_rate * (1.0 + global_entropy);

        if density_risk > self.pandemic_threshold && rng.gen_bool(self.base_trigger_chance) {
            calamities.push(EmergentCalamity {
                r#type: "PANDEMIC".to_string(),
                epicenter_zone_id: 1, // Simplified for now
                intensity: 0.8,
                description: "A deadly plague born from overcrowding and high entropy sweeps the lands.".to_string(),
                trigger_tick: tick,
            });
        }

        if war_risk > self.war_threshold && rng.gen_bool(self.base_trigger_chance) {
            calamities.push(EmergentCalamity {
                r#type: "TOTAL_WAR".to_string(),
                epicenter_zone_id: 1,
                intensity: 0.9,
                description: "Irreconcilable differences and advanced weaponry spark a devastating global conflict.".to_string(),
                trigger_tick: tick,
            });
        }

        if eco_risk > self.ecological_collapse_threshold && rng.gen_bool(self.base_trigger_chance) {
            calamities.push(EmergentCalamity {
                r#type: "ECOLOGICAL_COLLAPSE".to_string(),
                epicenter_zone_id: 1,
                intensity: 0.85,
                description: "Relentless resource extraction leads to a catastrophic failure of the biosphere.".to_string(),
                trigger_tick: tick,
            });
        }

        // The ultimate filter
        if global_entropy > 0.95 && peak_tech_level > 0.9 && rng.gen_bool(self.base_trigger_chance * 2.0) {
            calamities.push(EmergentCalamity {
                r#type: "SYSTEM_OVERLOAD".to_string(),
                epicenter_zone_id: 0,
                intensity: 1.0,
                description: "The singularity breaches the containment. Reality itself splinters under the weight of computation.".to_string(),
                trigger_tick: tick,
            });
        }

        calamities
    }

    /// Calculate the devastating impact on actors surviving the calamity
    pub fn calculate_trauma_and_hunger_deltas(&self, active_calamities: &[EmergentCalamity]) -> (f32, f32) {
        let mut trauma_delta = 0.0;
        let mut hunger_delta = 0.0;

        for c in active_calamities {
            match c.r#type.as_str() {
                "PANDEMIC" => {
                    trauma_delta += 0.3 * c.intensity;
                    hunger_delta += 0.1 * c.intensity;
                }
                "TOTAL_WAR" => {
                    trauma_delta += 0.5 * c.intensity;
                    hunger_delta += 0.3 * c.intensity;
                }
                "ECOLOGICAL_COLLAPSE" => {
                    trauma_delta += 0.2 * c.intensity;
                    hunger_delta += 0.5 * c.intensity;
                }
                "SYSTEM_OVERLOAD" => {
                    trauma_delta += 0.8 * c.intensity;
                    hunger_delta += 0.8 * c.intensity;
                }
                _ => {}
            }
        }

        (trauma_delta.clamp(0.0, 1.0), hunger_delta.clamp(0.0, 1.0))
    }
}
