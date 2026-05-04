<?php

namespace Database\Seeders;

use App\Modules\World\Models\Material;
use Illuminate\Database\Seeder;

class SymbolicMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $materials = [
            [
                'name' => 'Thờ cúng Tổ tiên',
                'slug' => 'ancestor-worship',
                'description' => 'Tập tục kết nối giữa quá khứ và hiện tại, đề cao lòng hiếu thảo và sự gắn kết dòng họ.',
                'ontology' => Material::ONTOLOGY_SYMBOLIC,
                'lifecycle' => Material::LIFECYCLE_ACTIVE,
                'pressure_coefficients' => [
                    'solidarity' => 0.2,
                    'rigidity' => 0.15,
                    'spirituality' => 0.25,
                ],
            ],
            [
                'name' => 'Lễ hội Đền đài',
                'slug' => 'shrine-festivals',
                'description' => 'Sự kiện cộng đồng tôn vinh các vị thần bản địa, tạo ra dòng năng lượng tâm linh mạnh mẽ.',
                'ontology' => Material::ONTOLOGY_SYMBOLIC,
                'lifecycle' => Material::LIFECYCLE_DORMANT,
                'pressure_coefficients' => [
                    'solidarity' => 0.3,
                    'spirituality' => 0.2,
                    'entropy' => 0.05,
                ],
            ],
            [
                'name' => 'Hệ thống Quan liêu Kỹ trị',
                'slug' => 'technocratic-bureaucracy',
                'description' => 'Định chế quản lý dựa trên năng lực và quy trình, tối ưu hóa nguồn lực nhưng khô khan.',
                'ontology' => Material::ONTOLOGY_INSTITUTIONAL,
                'lifecycle' => Material::LIFECYCLE_ACTIVE,
                'pressure_coefficients' => [
                    'openness' => 0.1,
                    'rigidity' => 0.2,
                    'stability' => 0.25,
                ],
            ],
            [
                'name' => 'Lý tưởng Tự do Ngữ nghĩa',
                'slug' => 'semantic-freedom',
                'description' => 'Niềm tin vào quyền giải phóng ý nghĩa khỏi các định chế cứng nhắc.',
                'ontology' => Material::ONTOLOGY_SYMBOLIC,
                'lifecycle' => Material::LIFECYCLE_DORMANT,
                'pressure_coefficients' => [
                    'openness' => 0.35,
                    'entropy' => 0.15,
                    'resilience' => -0.1,
                ],
            ],
        ];

        foreach ($materials as $material) {
            Material::updateOrCreate(['slug' => $material['slug']], $material);
        }
    }
}
