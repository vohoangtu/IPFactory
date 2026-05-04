<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TechnologySeeder extends Seeder
{
    public function run(): void
    {
        $technologies = [
            [
                'name' => 'Ngôn ngữ cơ bản',
                'code' => 'lang_basic',
                'description' => 'Khả năng giao tiếp sơ khai, giúp tăng tốc độ truyền đạt ý nghĩ.',
                'requirements' => json_encode([]),
                'effects' => json_encode([
                    'social_influence_bonus' => 0.1,
                    'logic_bonus' => 0.05
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Nông nghiệp sơ bộ',
                'code' => 'agriculture_basic',
                'description' => 'Biết cách trồng trọt, giảm nhu cầu tìm kiếm thức ăn liên tục.',
                'requirements' => json_encode(['lang_basic']),
                'effects' => json_encode([
                    'metabolism_bonus' => 0.1, // Reduce hunger decay
                    'resource_yield_bonus' => 0.2
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Công cụ đồ đá',
                'code' => 'stone_tools',
                'description' => 'Sử dụng đá để chế tạo công cụ, tăng khả năng khai thác và tự vệ.',
                'requirements' => json_encode([]),
                'effects' => json_encode([
                    'combat_bonus' => 0.1,
                    'resource_yield_bonus' => 0.1
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tự sự và Truyền thuyết',
                'code' => 'mythology',
                'description' => 'Khả năng kể chuyện, giúp gắn kết cộng đồng và giảm sợ hãi.',
                'requirements' => json_encode(['lang_basic']),
                'effects' => json_encode([
                    'fear_reduction' => 0.1,
                    'cohesion_bonus' => 0.15
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        DB::table('technologies')->insertOrIgnore($technologies);
    }
}
