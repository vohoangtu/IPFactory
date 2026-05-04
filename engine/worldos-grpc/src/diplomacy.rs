use std::collections::HashMap;

pub struct FactionRelation {
    pub from_id: i32,
    pub to_id: i32,
    pub tension: f32, // 0.0 (friend) to 1.0 (war)
}

pub struct DiplomacyEngine {
    relations: HashMap<(i32, i32), f32>,
}

impl DiplomacyEngine {
    pub fn new(relations_list: Vec<crate::worldos::simulation::FactionRelation>) -> Self {
        let mut map = HashMap::new();
        for r in relations_list {
            map.insert((r.faction_a, r.faction_b), r.tension);
            // Quan hệ đối xứng nếu chưa có quan hệ ngược lại
            if !map.contains_key(&(r.faction_b, r.faction_a)) {
                map.insert((r.faction_b, r.faction_a), r.tension);
            }
        }
        Self { relations: map }
    }

    pub fn get_tension(&self, f1: i32, f2: i32) -> f32 {
        if f1 == f2 {
            return 0.0;
        }
        *self.relations.get(&(f1, f2)).unwrap_or(&0.5) // Mặc định là trung lập (0.5)
    }

    /// Điều chỉnh trọng số hành vi dựa trên ngoại giao
    pub fn adjust_behavior_weights(
        &self,
        f1: i32,
        f2: i32,
        base_socialize: f32,
        base_conflict: f32,
    ) -> (f32, f32) {
        let tension = self.get_tension(f1, f2);
        
        // Căng thẳng cao (Chiến tranh) -> Giảm Socialize, Tăng Conflict
        let n_socialize = base_socialize * (1.0 - tension);
        let n_conflict = base_conflict * (1.0 + tension);
        
        (n_socialize, n_conflict)
    }
}
