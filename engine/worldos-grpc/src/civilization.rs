use std::collections::HashMap;
use crate::worldos::simulation::{CivilizationMetrics, ZoneMetrics};

pub struct CivilizationAggregator;

impl CivilizationAggregator {
    pub fn aggregate(
        zone_ids: &[u32],
        new_hungers: &[f32],
        new_energies: &[f32],
        new_fears: &[f32],
        new_traumas: &[f32],
        resource_deltas: &[f32],
    ) -> CivilizationMetrics {
        let mut zone_data: HashMap<u32, Vec<(f32, f32, f32, f32, f32)>> = HashMap::new();
        
        let count = zone_ids.len();
        for i in 0..count {
            let zid = zone_ids[i];
            zone_data.entry(zid).or_default().push((
                new_hungers[i],
                new_energies[i],
                new_fears[i],
                new_traumas[i],
                resource_deltas[i]
            ));
        }

        let mut zone_stats = Vec::new();
        let mut total_fear = 0.0;
        let mut total_trauma = 0.0;

        for (zid, data) in zone_data {
            let z_count = data.len() as f32;
            let sum_h: f32 = data.iter().map(|x| x.0).sum();
            let sum_e: f32 = data.iter().map(|x| x.1).sum();
            let sum_f: f32 = data.iter().map(|x| x.2).sum();
            let sum_t: f32 = data.iter().map(|x| x.3).sum();
            let sum_res: f32 = data.iter().map(|x| x.4).sum();

            let avg_f = sum_f / z_count;
            let avg_t = sum_t / z_count;

            zone_stats.push(ZoneMetrics {
                zone_id: zid,
                avg_hunger: sum_h / z_count,
                avg_energy: sum_e / z_count,
                avg_fear: avg_f,
                avg_trauma: avg_t,
                total_resource_extracted: sum_res,
                social_cohesion: (1.0 - avg_f * 0.7 - avg_t * 0.3).clamp(0.0, 1.0),
            });
            
            total_fear += avg_f;
            total_trauma += avg_t;
        }

        let num_zones = zone_stats.len() as f32;
        CivilizationMetrics {
            global_entropy: if num_zones > 0.0 {
                (total_fear / num_zones * 0.6 + total_trauma / num_zones * 0.4).clamp(0.0, 1.0)
            } else {
                0.0
            },
            zone_stats,
            urban_density: vec![],
        }
    }
}
