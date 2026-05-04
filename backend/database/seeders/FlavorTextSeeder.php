<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FlavorTextSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // Entropy
            ['vector_key' => 'entropy', 'min_value' => 0.0, 'max_value' => 0.2, 'text' => 'Trật tự tuyệt đối ngự trị, vạn vật tĩnh lặng.', 'locale' => 'vi'],
            ['vector_key' => 'entropy', 'min_value' => 0.2, 'max_value' => 0.5, 'text' => 'Cấu trúc ổn định, sự sống bắt đầu nảy nở.', 'locale' => 'vi'],
            ['vector_key' => 'entropy', 'min_value' => 0.5, 'max_value' => 0.8, 'text' => 'Hỗn mang nhen nhóm, các vết rạn xuất hiện.', 'locale' => 'vi'],
            ['vector_key' => 'entropy', 'min_value' => 0.8, 'max_value' => 1.0, 'text' => 'Thế giới đang tan rã vào hư vô.', 'locale' => 'vi'],
            
            // Epistemic Instability (Fog of War)
            ['vector_key' => 'epistemic_instability', 'min_value' => 0.0, 'max_value' => 0.3, 'text' => 'Tri thức sáng tỏ, lịch sử được ghi chép minh bạch.', 'locale' => 'vi'],
            ['vector_key' => 'epistemic_instability', 'min_value' => 0.3, 'max_value' => 0.7, 'text' => 'Sự thật bị bóp méo, những lời đồn đại dần thay thế sự thật.', 'locale' => 'vi'],
            ['vector_key' => 'epistemic_instability', 'min_value' => 0.7, 'max_value' => 1.0, 'text' => 'Kỷ nguyên mạt pháp, mọi tri thức đều trở thành thần thoại mơ hồ.', 'locale' => 'vi'],

            // Stability
            ['vector_key' => 'stability_index', 'min_value' => 0.0, 'max_value' => 0.3, 'text' => 'Nền tảng lung lay, những cơn địa chấn chính trị liên hồi.', 'locale' => 'vi'],
            ['vector_key' => 'stability_index', 'min_value' => 0.3, 'max_value' => 0.7, 'text' => 'Cân bằng mong manh giữa các thế lực.', 'locale' => 'vi'],
            ['vector_key' => 'stability_index', 'min_value' => 0.7, 'max_value' => 1.0, 'text' => 'Thái bình thịnh trị, thiên hạ thái bình.', 'locale' => 'vi'],
        ];

        foreach ($data as $row) {
            DB::table('flavor_texts')->updateOrInsert(
                ['vector_key' => $row['vector_key'], 'min_value' => $row['min_value'], 'locale' => $row['locale']],
                $row
            );
        }
    }
}
