pub struct UrbanGrowthEngine {
    grid_size: usize,
}

impl UrbanGrowthEngine {
    pub fn new(grid_size: usize) -> Self {
        Self { grid_size }
    }

    /// Tính toán mật độ đô thị dựa trên các trường Vectorized
    pub fn compute_density(
        &self,
        population: f32,
        biomass: f32,
        industry: f32,
        entropy: f32,
    ) -> f32 {
        // Mật độ đô thị tỉ lệ thuận với dân số và công nghiệp
        // Nhưng bị kìm hãm bởi Entropy (sự hỗn loạn/phân rã)
        let base_density = (population * 0.7) + (industry * 0.3);
        let stability_factor = 1.0 - (entropy * 0.5);
        
        // Cần một lượng tài nguyên sinh khối nhất định để duy trì sự sống đô thị
        let biomass_requirement = if population > 50.0 { 0.2 } else { 0.0 };
        
        if biomass < biomass_requirement {
            return (base_density * 0.5 * stability_factor).clamp(0.0, 1.0);
        }

        (base_density * stability_factor).clamp(0.0, 1.0)
    }

    /// Trình bày lưới đô thị hóa cho toàn cầu
    pub fn generate_urban_grid(
        &self,
        pop_field: &[f32],
        biomass_field: &[f32],
        industry_field: &[f32],
        entropy_field: &[f32],
    ) -> Vec<f32> {
        let mut grid = Vec::with_capacity(pop_field.len());
        for i in 0..pop_field.len() {
            grid.push(self.compute_density(
                pop_field[i],
                biomass_field[i],
                industry_field[i],
                entropy_field[i],
            ));
        }
        grid
    }
}
