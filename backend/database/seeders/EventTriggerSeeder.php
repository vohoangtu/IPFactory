<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventTriggerSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'event_type' => 'unrest',
                'name_template' => 'Làn sóng Bất ổn: {material}',
                'prompt_fragment' => 'Sự khan hiếm {material} đã đẩy quần chúng vào cơn phẫn nộ.',
                'threshold_rules' => [
                    ['key' => 'entropy', 'op' => '>=', 'value' => 0.7],
                    ['key' => 'stability_index', 'op' => '<=', 'value' => 0.4],
                ],
                'scope' => 'universe',
                'probability' => 0.3,
                'cooldown_ticks' => 10,
            ],
            [
                'event_type' => 'secession',
                'name_template' => 'Ly khai tại {zone}',
                'prompt_fragment' => 'Sự khác biệt về ý thức hệ đã khiến {zone} tuyên bố độc lập.',
                'threshold_rules' => null,
                'scope' => 'universe',
                'probability' => 0.2,
                'cooldown_ticks' => 10,
            ],
            [
                'event_type' => 'formation',
                'name_template' => 'Sự trỗi dậy của {institution}',
                'prompt_fragment' => 'Giữa lúc khủng hoảng, {institution} đã nổi lên như một ngọn hải đăng mới.',
                'threshold_rules' => null,
                'scope' => 'universe',
                'probability' => 0.2,
                'cooldown_ticks' => 10,
            ],
            [
                'event_type' => 'collapse',
                'name_template' => 'Đại Sụp Đổ: {institution}',
                'prompt_fragment' => 'Sự suy kiệt của ổn định thực tại khiến {institution} tan rã, kéo theo một thời kỳ hỗn mang.',
                'threshold_rules' => [
                    ['key' => 'stability_index', 'op' => '<=', 'value' => 0.25],
                ],
                'scope' => 'universe',
                'probability' => 0.4,
                'cooldown_ticks' => 10,
            ],
            [
                'event_type' => 'stability_decay',
                'name_template' => 'Sự Suy Tàn của Trật Tự',
                'prompt_fragment' => 'Tính liên kết của lốt thực tại này đang mỏng dần. Mọi định chế đều cảm thấy sự lung lay.',
                'threshold_rules' => [
                    ['key' => 'sci', 'op' => '<=', 'value' => 0.35],
                ],
                'scope' => 'universe',
                'probability' => 0.5,
                'cooldown_ticks' => 15,
            ],
            [
                'event_type' => 'myth_scar',
                'name_template' => 'Di chứng Thần thoại: {scar}',
                'prompt_fragment' => 'Dòng chảy lịch sử bị bẻ cong bởi sẹo thần thoại {scar}.',
                'threshold_rules' => null,
                'scope' => 'universe',
                'probability' => 0.2,
                'cooldown_ticks' => 10,
            ],
            [
                'event_type' => 'crisis',
                'name_template' => 'Khủng hoảng',
                'prompt_fragment' => 'Entropy cao và ổn định thấp báo hiệu một giai đoạn khủng hoảng.',
                'threshold_rules' => [
                    ['key' => 'entropy', 'op' => '>=', 'value' => 0.6],
                    ['key' => 'stability_index', 'op' => '<=', 'value' => 0.5],
                ],
                'scope' => 'universe',
                'probability' => 0.2,
                'cooldown_ticks' => 20,
            ],
            [
                'event_type' => 'golden_age',
                'name_template' => 'Kỷ Nguyên Hoàng Kim',
                'prompt_fragment' => 'Khi các trường hấp dấn đạt độ cân bằng tuyệt mỹ, một thời đại hưng thịnh chưa từng có bắt đầu.',
                'threshold_rules' => [
                    ['key' => 'entropy', 'op' => '<=', 'value' => 0.3],
                    ['key' => 'stability_index', 'op' => '>=', 'value' => 0.7],
                ],
                'scope' => 'universe',
                'probability' => 0.18,
                'cooldown_ticks' => 25,
            ],
            [
                'event_type' => 'power_dominance',
                'name_template' => 'Áp Chế Quyền Lực',
                'prompt_fragment' => 'Trường Quyền Lực đã nuốt chửng mọi khát vọng khác. Một trật tự sắt đá đang hình thành.',
                'threshold_rules' => [
                    ['key' => 'power', 'op' => '>=', 'value' => 0.8],
                ],
                'scope' => 'universe',
                'probability' => 0.3,
                'cooldown_ticks' => 20,
            ],
            [
                'event_type' => 'knowledge_explosion',
                'name_template' => 'Bùng Nổ Tri Thức',
                'prompt_fragment' => 'Những bức màn của sự thiếu hiểu biết bị xé toạc. Văn minh bước vào một kỷ nguyên khai sáng chói lòa.',
                'threshold_rules' => [
                    ['key' => 'knowledge', 'op' => '>=', 'value' => 0.8],
                ],
                'scope' => 'universe',
                'probability' => 0.25,
                'cooldown_ticks' => 20,
            ],
            [
                'event_type' => 'fork',
                'name_template' => 'Phân nhánh vũ trụ',
                'prompt_fragment' => 'Ngưỡng criticality vượt qua, vũ trụ phân nhánh tại điểm quyết định.',
                'threshold_rules' => [
                    ['key' => 'entropy', 'op' => '>=', 'value' => 0.8],
                ],
                'scope' => 'universe',
                'probability' => 0.15,
                'cooldown_ticks' => 30,
            ],
        ];

        foreach ($data as $row) {
            $insert = $row;
            if (isset($insert['threshold_rules']) && is_array($insert['threshold_rules'])) {
                $insert['threshold_rules'] = json_encode($insert['threshold_rules']);
            }
            DB::table('event_triggers')->updateOrInsert(
                ['event_type' => $row['event_type']],
                $insert
            );
        }
    }
}
