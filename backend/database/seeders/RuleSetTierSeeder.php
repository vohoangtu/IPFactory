<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RuleSetTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            [
                'tier' => 0,
                'label' => 'Thực Tế',
                'description' => 'Vật lý thuần túy',
                'ontology' => 'Thực tại = vật chất + năng lượng + thông tin. Không có gì vượt khoa học.',
                'entity_ceiling' => 'post_human_tech',
                'examples' => json_encode(["Modern Earth", "Hard Sci-fi"]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tier' => 1,
                'label' => 'Võ Thuật',
                'description' => 'Thể chất được tối ưu hoàn toàn',
                'ontology' => 'Con người có thể đạt giới hạn sinh học tuyệt đối. Không có nội lực, không có siêu nhiên.',
                'entity_ceiling' => 'grandmaster_fighter',
                'examples' => json_encode(["Ip Man world", "Rocky world"]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tier' => 2,
                'label' => 'Kiếm Hiệp',
                'description' => 'Nội công sơ khai, khinh công',
                'ontology' => 'Khí lực nội tại có thể được vận dụng. Người đỉnh cao có thể bay lướt, chịu đòn siêu thường.',
                'entity_ceiling' => 'jianghu_legend',
                'examples' => json_encode(["Jin Yong world", "Gu Long world"]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tier' => 3,
                'label' => 'Cao Võ',
                'description' => 'Nội lực siêu việt, phá giới hạn sinh học',
                'ontology' => 'Năng lượng nội tại có thể được externalize. Chưởng lực, kiếm khí hữu hình.',
                'entity_ceiling' => 'martial_saint',
                'examples' => json_encode(["Coiling Dragon early", "Stellar Transformation early"]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tier' => 4,
                'label' => 'Tiên Hiệp',
                'description' => 'Tu luyện đạt bất tử, điều khiển thiên địa',
                'ontology' => 'Linh khí thiên địa có thể hấp thu. Thể xác và linh hồn có thể siêu việt tử vong.',
                'entity_ceiling' => 'tribulation_transcendence',
                'examples' => json_encode(["Xianxia classical", "I Shall Seal the Heavens"]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tier' => 5,
                'label' => 'Thiên Đạo',
                'description' => 'Luật vận hành vũ trụ có thể được comprehend và alter',
                'ontology' => 'Thiên Đạo là bộ luật chạy vũ trụ. Entity đủ mạnh có thể đọc, hiểu, thậm chí sửa đổi.',
                'entity_ceiling' => 'dao_sovereign',
                'examples' => json_encode(["Desolate Era end-game", "Lord of Mysteries"]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tier' => 6,
                'label' => 'Đạo',
                'description' => 'Bản chất tuyệt đối — vượt khái niệm tồn tại/không tồn tại',
                'ontology' => 'Không còn phân biệt self/world. Entity = Đạo. Mọi thứ là biểu hiện của một nguồn.',
                'entity_ceiling' => 'beyond_concept',
                'examples' => json_encode(["Buddhist enlightenment metaphysics", "Daoist ultimate"]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tier' => 7,
                'label' => 'Hồng Mông',
                'description' => 'Trước khi có vũ trụ — chaos nguyên thuỷ vô phân biệt',
                'ontology' => 'Không có luật. Không có thực tại cố định. Mọi khả năng cùng tồn tại.',
                'entity_ceiling' => 'primordial_chaos',
                'examples' => json_encode(["Honkai: Star Rail Nihility", "Pre-creation void"]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($tiers as $tier) {
            DB::table('ruleset_tiers')->updateOrInsert(
                ['tier' => $tier['tier']],
                $tier
            );
        }
    }
}
