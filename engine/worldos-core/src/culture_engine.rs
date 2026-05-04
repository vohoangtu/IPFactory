use crate::types::{ActorTable, BehaviorContext, ZoneStateSerial, NarrativeTag};


/// Engine for the Culture Layer: Meme Propagation & Mutation
pub struct CultureEngine {
    pub drift_rate: f32,
}

impl CultureEngine {
    pub fn new(drift_rate: f32) -> Self {
        Self { drift_rate }
    }

    /// Update memes and cultural traits based on zone culture and social context
    pub fn update(&self, table: &mut ActorTable, _context: &BehaviorContext, zones: &[ZoneStateSerial]) {
        let count = table.ids.len();
        for i in 0..count {
            let zone_id = table.zone_ids[i] as usize;
            if let Some(zone_serial) = zones.get(zone_id) {
                let cult = &zone_serial.state.cultural;
                
                // 1. Meme Mutation based on Local Entropy
                // High entropy = higher chance of random meme bit flips
                if zone_serial.state.entropy > 0.7 {
                    let mutation_seed = (table.ids[i] ^ (zone_serial.id as u64)) % 64;
                    table.memes_mask[i] ^= 1 << mutation_seed;
                }

                // 2. Meme Propagation (Simplified)
                // If institutional respect is high, actors tend to adopt 'standard' memes
                if cult.institutional_respect > 0.8 {
                    table.memes_mask[i] |= 0b1; // Adopt bit 0 (Order/Rule of Law)
                }

                // 3. Trait Evolution based on Innovation Openness
                if cult.innovation_openness > 0.9 {
                    table.traits_mask[i] |= 1 << 4; // Curiosity trait
                }
            }
        }
    }

    /// Global narrative synthesis: identifies dominant cultural themes across all zones
    pub fn generate_narrative_tags(&self, zones: &[ZoneStateSerial]) -> Vec<NarrativeTag> {
        let mut tags = Vec::new();
        if zones.is_empty() { return tags; }

        let n = zones.len() as f64;
        let avg_tradition: f64 = zones.iter().map(|z| z.state.cultural.tradition_rigidity).sum::<f64>() / n;
        let avg_innovation: f64 = zones.iter().map(|z| z.state.cultural.innovation_openness).sum::<f64>() / n;
        let avg_trust: f64 = zones.iter().map(|z| z.state.cultural.collective_trust).sum::<f64>() / n;
        let avg_violence: f64 = zones.iter().map(|z| z.state.cultural.violence_tolerance).sum::<f64>() / n;

        if avg_tradition > 0.7 {
            tags.push(NarrativeTag { slug: "traditionalist_stronghold".into(), weight: avg_tradition as f32 });
        }
        if avg_innovation > 0.7 {
            tags.push(NarrativeTag { slug: "renaissance_flame".into(), weight: avg_innovation as f32 });
        }
        if avg_violence > 0.6 {
            tags.push(NarrativeTag { slug: "age_of_strife".into(), weight: avg_violence as f32 });
        }
        if avg_trust < 0.3 {
            tags.push(NarrativeTag { slug: "paranoia_recursive".into(), weight: (1.0 - avg_trust) as f32 });
        }

        tags
    }
}
