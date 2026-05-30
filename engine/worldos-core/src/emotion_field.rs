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

    fn make_field(fear: f64, trust: f64, stress: f64) -> EmotionField {
        EmotionField { fear, trust, stress, ..Default::default() }
    }

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

    #[test]
    fn test_all_emotions_decay() {
        let mut context = BehaviorContext {
            emotion_fields: vec![make_field(0.5, 0.8, 0.3)],
            ..Default::default()
        };
        let table = ActorTable::default();
        let engine = EmotionFieldEngine::new(0.1);

        engine.update(&mut context, &table);
        let field = &context.emotion_fields[0];
        assert!((field.fear - 0.4).abs() < 1e-6, "fear should decay");
        assert!((field.trust - 0.7).abs() < 1e-6, "trust should decay");
        assert!((field.stress - 0.2).abs() < 1e-6, "stress should decay");
    }

    #[test]
    fn test_multiple_fields_decay_independently() {
        let mut context = BehaviorContext {
            emotion_fields: vec![
                make_field(0.9, 0.1, 0.5),
                make_field(0.2, 0.9, 0.8),
            ],
            ..Default::default()
        };
        let table = ActorTable::default();
        let engine = EmotionFieldEngine::new(0.2);

        engine.update(&mut context, &table);
        assert!((context.emotion_fields[0].fear - 0.7).abs() < 1e-6, "field 0 fear decay");
        assert!((context.emotion_fields[1].trust - 0.7).abs() < 1e-6, "field 1 trust decay");
    }

    #[test]
    fn test_decay_never_goes_below_zero() {
        let mut context = BehaviorContext {
            emotion_fields: vec![make_field(0.05, 0.0, 0.0)],
            ..Default::default()
        };
        let table = ActorTable::default();
        let engine = EmotionFieldEngine::new(0.2);

        engine.update(&mut context, &table);
        let field = &context.emotion_fields[0];
        assert!(field.fear >= 0.0, "fear should not go negative");
        assert!(field.trust >= 0.0, "trust should not go negative");
        assert!(field.stress >= 0.0, "stress should not go negative");
    }

    #[test]
    fn test_impulse_adds_to_field() {
        let mut context = BehaviorContext {
            emotion_fields: vec![make_field(0.3, 0.3, 0.3)],
            ..Default::default()
        };
        let table = ActorTable::default();
        let engine = EmotionFieldEngine::new(0.0); // no natural decay

        context.emotion_fields[0].apply_impulse(&EmotionImpulse {
            fear: 0.5,
            trust: 0.2,
            stress: -0.1,
        });

        engine.update(&mut context, &table);
        let field = &context.emotion_fields[0];
        assert!(field.fear > 0.7, "fear should increase from impulse");
        assert!(field.trust > 0.4, "trust should increase from impulse");
    }

    #[test]
    fn test_default_field_is_zeroed() {
        let field = EmotionField::default();
        assert_eq!(field.fear, 0.0);
        assert_eq!(field.trust, 0.0);
        assert_eq!(field.stress, 0.0);
    }
}
