use crate::types::{EmotionField, BehaviorContext, ActorTable};

/// Engine for the Macro Layer: Civilization Emotion Field
/// Handles diffusion and decay of emotions across zones.
pub struct EmotionFieldEngine {
    pub decay_rate: f32, // Rate at which emotions return to 0 (e.g. 0.05)
}

impl EmotionFieldEngine {
    pub fn new(decay_rate: f32) -> Self {
        Self { decay_rate }
    }

    /// Update the emotion fields in the context
    pub fn update(&self, context: &mut BehaviorContext, _table: &ActorTable) {
        // 1. Apply decay to all zone emotions
        for field in &mut context.emotion_fields {
            field.fear = (field.fear - self.decay_rate).max(0.0);
            field.anger = (field.anger - self.decay_rate).max(0.0);
            field.hope = (field.hope - self.decay_rate).max(0.0);
            field.trust = (field.trust - self.decay_rate).max(0.0);
        }

        // 2. Placeholder: Diffusion 
        // In a real grid, emotions would leak to adjacent zones.
        // For now, we simulate events adding to emotions in higher layers.
    }

    /// Add an emotional impulse to a specific zone (e.g. from an event)
    pub fn add_impulse(&self, context: &mut BehaviorContext, zone_id: usize, impulse: EmotionField) {
        if let Some(field) = context.emotion_fields.get_mut(zone_id) {
            field.fear = (field.fear + impulse.fear).min(1.0);
            field.anger = (field.anger + impulse.anger).min(1.0);
            field.hope = (field.hope + impulse.hope).min(1.0);
            field.trust = (field.trust + impulse.trust).min(1.0);
        }
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_emotion_decay() {
        let mut context = BehaviorContext {
            emotion_fields: vec![EmotionField {
                fear: 0.5,
                ..Default::default()
            }],
            ..Default::default()
        };
        let table = ActorTable::default();
        let engine = EmotionFieldEngine::new(0.1);

        engine.update(&mut context, &table);
        assert!((context.emotion_fields[0].fear - 0.4).abs() < 1e-6);
    }
}
