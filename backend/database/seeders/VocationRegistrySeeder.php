<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VocationRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $vocations = [
            // --- TIER 0-1: CIVILIAN/SCHOLARLY ---
            // --- MARTIAL ARCHETYPE (10) ---
            $this->makeVocation('v_warrior', 'Chiến Binh', 0, ['martial'], [0, 0.4, 0.8, 0.2, 1, 0, 1, 0]),
            $this->makeVocation('v_general', 'Đại Tướng Quân', 0, ['martial', 'order'], [0, 0.8, 1, 0, 0.6, 0.4, 0.8, 0.2]),
            $this->makeVocation('v_swordsman', 'Kiếm Khách', 2, ['martial', 'wuxia'], [0, 0.6, 0.4, 0.4, 1, 0, 1, 0.2]),
            $this->makeVocation('v_spearman', 'Thương Thủ', 0, ['martial'], [0, 0.5, 0.8, 0.2, 1, 0, 1, 0]),
            $this->makeVocation('v_archer', 'Cung Thủ', 0, ['martial'], [0, 0.5, 0.8, 0.2, 1, 0, 0.6, 0]),
            $this->makeVocation('v_cavalry', 'Kỵ Binh', 0, ['martial'], [0, 0.7, 0.9, 0.1, 1, 0, 1, 0]),
            $this->makeVocation('v_shield_guard', 'Thuẫn Vệ', 0, ['martial', 'order'], [0, 0, 1, 0, 1, 0.5, 1, 0]),
            $this->makeVocation('v_martial_saint', 'Võ Thánh', 3, ['martial', 'peak'], [0, 1, 1, 0, 1, 0, 1, 1]),
            $this->makeVocation('v_arena_champion', 'Vô Địch Đấu Trường', 1, ['martial'], [0, 0.8, 0.2, 0.8, 1, 0, 1, 0]),
            $this->makeVocation('v_mercenary', 'Lính Thuê', 1, ['martial', 'chaos'], [0, 0.8, 0, 1, 1, 0, 1, 0]),

            // --- SCHOLARLY ARCHETYPE (10) ---
            $this->makeVocation('v_scholar', 'Học Giả', 0, ['scholar'], [0.8, 0, 1, 0, 0.5, 0.2, 0, 0.2]),
            $this->makeVocation('v_strategist', 'Mưu Sĩ', 1, ['scholar', 'order'], [0.6, 0.4, 1, 0, 0.6, 0.4, 0, 0.4]),
            $this->makeVocation('v_poet', 'Thi Nhân', 1, ['scholar', 'chaos'], [1, 0, 0, 1, 0.2, 0.3, 0, 0.5]),
            $this->makeVocation('v_calligrapher', 'Thư Pháp Sư', 2, ['scholar', 'wuxia'], [1, 0, 1, 0.2, 0.5, 0, 0, 0.8]),
            $this->makeVocation('v_historian', 'Sử Quan', 0, ['scholar', 'order'], [0.5, 0, 1, 0, 0.8, 0.2, 0, 0.1]),
            $this->makeVocation('v_philosopher', 'Triết Gia', 0, ['scholar'], [0.9, 0, 0.5, 0.5, 0.2, 0.5, 0, 0.8]),
            $this->makeVocation('v_astrologer', 'Chiêm Tinh Gia', 2, ['scholar', 'metaphysical'], [0.5, 0, 1, 0.2, 0.4, 0.1, 0, 1]),
            $this->makeVocation('v_diplomat', 'Ngoại Giao Quan', 0, ['scholar', 'order'], [0.2, 0, 1, 0, 0.8, 0.5, 0, 0.2]),
            $this->makeVocation('v_alchemist_basic', 'Luyện Đan Sư (Sơ)', 2, ['scholar', 'wuxia'], [1, 0, 1, 0, 0.5, 0.5, 0, 0.8]),
            $this->makeVocation('v_physician', 'Y Sĩ', 0, ['scholar', 'altruism'], [1, 0, 1, 0, 0.5, 1, 0, 0.3]),

            // --- SHADOW ARCHETYPE (10) ---
            $this->makeVocation('v_assassin', 'Thích Khách', 1, ['shadow', 'chaos'], [0, 1, 0.2, 0.8, 1, 0, 0.8, 0.2]),
            $this->makeVocation('v_thief', 'Đạo Tặc', 1, ['shadow', 'chaos'], [0, 0.2, 0, 1, 1, 0, 0.5, 0]),
            $this->makeVocation('v_spy', 'Gián Điệp', 1, ['shadow', 'order'], [0, 0.5, 1, 0, 1, 0, 0.5, 0.2]),
            $this->makeVocation('v_ninja_basic', 'Ninja Hạ Nhẫn', 3, ['shadow', 'anime'], [0, 0.5, 0.8, 0.2, 1, 0, 1, 0.5]),
            $this->makeVocation('v_shadow_guard', 'Ảnh Vệ', 2, ['shadow', 'order'], [0, 0.4, 1, 0, 1, 0.5, 1, 0.2]),
            $this->makeVocation('v_informant', 'Kẻ Tin Cẩn', 1, ['shadow'], [0, 0, 0.5, 0.5, 1, 0, 0, 0]),
            $this->makeVocation('v_rogue_scout', 'Trinh Sát Hoang Dã', 1, ['shadow', 'nature'], [0, 0.2, 0.5, 0.5, 1, 0, 0.8, 0]),
            $this->makeVocation('v_poison_master', 'Độc Sư', 2, ['shadow', 'destruction'], [0, 1, 0.5, 0.5, 1, 0, 0, 0.8]),
            $this->makeVocation('v_infiltrator', 'Kẻ Xâm Nhập', 4, ['shadow', 'scifi'], [0, 0.5, 1, 0.2, 1, 0, 0.5, 0.5]),
            $this->makeVocation('v_phantom_thief', 'Quái Đạo', 2, ['shadow', 'chaos'], [1, 0, 0, 1, 0.5, 0.5, 0.2, 0.5]),

            // --- DIVINE ARCHETYPE (10) ---
            $this->makeVocation('v_priest', 'Tế Ty', 2, ['divine', 'order'], [0.5, 0, 1, 0, 0.5, 0.8, 0, 0.9]),
            $this->makeVocation('v_monk', 'Tu Sĩ', 2, ['divine', 'order'], [0, 0, 1, 0, 0.8, 0.2, 0.5, 0.9]),
            $this->makeVocation('v_cultivator_fire', 'Tu Sĩ Hệ Hoả', 4, ['divine', 'xianxia'], [0, 1, 0, 1, 0.5, 0.2, 0.5, 1]),
            $this->makeVocation('v_sword_immortal', 'Kiếm Tiên', 4, ['divine', 'xianxia'], [0, 0.8, 0.8, 0.2, 0.5, 0, 0.8, 1]),
            $this->makeVocation('v_demon_cultist', 'Ma Tu', 4, ['divine', 'chaos', 'xianxia'], [0, 1, 0, 1, 1, 0, 0.5, 1]),
            $this->makeVocation('v_sect_leader', 'Tông Chủ', 4, ['divine', 'order', 'xianxia'], [0, 0.5, 1, 0, 1, 0.5, 0.5, 1]),
            $this->makeVocation('v_saint', 'Thánh Nhân', 5, ['divine', 'altruism'], [1, 0, 1, 0, 0.2, 1, 0, 1]),
            $this->makeVocation('v_god_hand', 'Thần Chi Thủ', 5, ['divine', 'creation'], [1, 0.2, 1, 0, 0.5, 0.5, 0.5, 1]),
            $this->makeVocation('v_shaman', 'Thầy Mo', 1, ['divine', 'nature'], [0.5, 0, 0.5, 0.5, 0.8, 0.5, 0, 0.8]),
            $this->makeVocation('v_oracle', 'Nhà Tiên Tri', 3, ['divine', 'metaphysical'], [0.2, 0, 1, 0.2, 0.5, 0.5, 0, 1]),

            // --- CRAFT ARCHETYPE (10) ---
            $this->makeVocation('v_farmer', 'Nông Dân', 0, ['craft', 'production'], [1, 0, 1, 0, 1, 0, 0.5, 0]),
            $this->makeVocation('v_blacksmith', 'Thợ Rèn', 0, ['craft', 'production'], [1, 0, 1, 0, 0.5, 0, 0.8, 0]),
            $this->makeVocation('v_merchant', 'Thương Nhân', 0, ['craft', 'trade'], [0.2, 0, 1, 0.5, 0.8, 0.2, 0, 0]),
            $this->makeVocation('v_carpenter', 'Thợ Mộc', 0, ['craft', 'production'], [1, 0, 1, 0, 0.5, 0, 0.6, 0]),
            $this->makeVocation('v_weaver', 'Thợ Dệt', 0, ['craft', 'production'], [1, 0, 1, 0, 0.5, 0, 0.2, 0]),
            $this->makeVocation('v_miner_spirit', 'Thợ Mỏ Linh Thạch', 4, ['craft', 'xianxia'], [0, 0.2, 1, 0, 1, 0, 1, 0.5]),
            $this->makeVocation('v_chef', 'Đầu Bếp', 0, ['craft', 'production'], [1, 0, 1, 0.2, 0.5, 0.5, 0, 0]),
            $this->makeVocation('v_shipwright', 'Thợ Đóng Tàu', 0, ['craft', 'production'], [1, 0, 1, 0, 0.5, 0, 1, 0]),
            $this->makeVocation('v_talisman_crafter', 'Chế Phù Sư', 3, ['craft', 'xianxia'], [1, 0, 1, 0.2, 0.8, 0, 0, 0.9]),
            $this->makeVocation('v_jeweler', 'Thợ Kim Hoàn', 0, ['craft', 'art'], [1, 0, 1, 0, 0.5, 0, 0.2, 0]),

            // --- ARCANE ARCHETYPE (10) ---
            $this->makeVocation('v_wizard', 'Pháp Sư', 2, ['arcane', 'fantasy'], [1, 0.5, 1, 0.2, 0.5, 0, 0, 1]),
            $this->makeVocation('v_necromancer', 'Thầy Pháp Chiêu Hồn', 2, ['arcane', 'destruction', 'chaos'], [0, 1, 0, 1, 1, 0, 0, 1]),
            $this->makeVocation('v_sorcerer', 'Thuật Sĩ', 2, ['arcane', 'chaos'], [0.5, 0.8, 0, 1, 0.5, 0, 0, 1]),
            $this->makeVocation('v_archmage', 'Đại Pháp Sư', 3, ['arcane', 'peak'], [1, 1, 1, 0.5, 0.5, 0.5, 0, 1]),
            $this->makeVocation('v_enchanter', 'Thuật Sĩ Cường Hoá', 2, ['arcane', 'order'], [1, 0, 1, 0, 0.8, 0.2, 0, 0.8]),
            $this->makeVocation('v_illusionist', 'Ảo Thuật Sư', 2, ['arcane', 'chaos'], [1, 0, 0.2, 1, 0.5, 0, 0, 0.9]),
            $this->makeVocation('v_runesmith', 'Thợ Rèn Phù Văn', 2, ['arcane', 'order'], [1, 0, 1, 0, 0.8, 0, 0.5, 0.9]),
            $this->makeVocation('v_druid', 'Tu Sĩ Thiên Nhiên', 2, ['arcane', 'nature'], [1, 0, 0.5, 0.5, 0.8, 0.8, 0, 0.9]),
            $this->makeVocation('v_summoner', 'Triệu Hoán Sư', 2, ['arcane'], [0.5, 0.5, 0.8, 0.5, 1, 0.2, 0, 0.9]),
            $this->makeVocation('v_void_priest', 'Tế Ty Hư Không', 4, ['arcane', 'chaos'], [0, 1, 0, 1, 1, 0, 0, 1]),

            // --- TECH ARCHETYPE (10) ---
            $this->makeVocation('v_engineer', 'Kỹ Sư', 0, ['tech', 'creation'], [1, 0, 1, 0, 0.5, 0, 0, 0]),
            $this->makeVocation('v_hacker', 'Tin Tặc', 4, ['tech', 'chaos'], [0.5, 0.5, 0, 1, 1, 0, 0, 0.5]),
            $this->makeVocation('v_cyborg_soldier', 'Chiến Binh Cơ Giới', 4, ['tech', 'martial'], [0, 0.8, 1, 0, 1, 0, 1, 0.2]),
            $this->makeVocation('v_mechanic', 'Thợ Máy', 0, ['tech'], [1, 0, 1, 0, 0.5, 0, 0.6, 0]),
            $this->makeVocation('v_pilot', 'Phi Công', 4, ['tech'], [0, 0, 1, 0.5, 1, 0, 0.2, 0]),
            $this->makeVocation('v_data_analyst', 'Chuyên Viên Dữ Liệu', 0, ['tech', 'order'], [0, 0, 1, 0, 0.8, 0.2, 0, 0]),
            $this->makeVocation('v_geneticist', 'Nhà Di Truyền Học', 4, ['tech', 'creation'], [1, 0.2, 1, 0.2, 0.5, 0.5, 0, 0.5]),
            $this->makeVocation('v_android_technician', 'Kỹ Thuật Viên Android', 4, ['tech'], [1, 0, 1, 0, 0.5, 0, 0, 0]),
            $this->makeVocation('v_nanotech_specialist', 'Chuyên Gia Nano', 4, ['tech'], [1, 0.5, 1, 0.2, 0.5, 0, 0, 0.5]),
            $this->makeVocation('v_starship_captain', 'Thuyền Trưởng Phi Thuyền', 4, ['tech', 'order'], [0, 0.2, 1, 0, 1, 0.5, 0, 0.2]),

            // --- ULTIMATE ARCHETYPE (2) ---
            $this->makeVocation('v_dao_sovereign', 'Đạo Tổ', 6, ['ultimate', 'dao'], [1, 1, 1, 1, 1, 1, 1, 1]),
            $this->makeVocation('v_chaos_entity', 'Thái Sơ Chi Thể', 7, ['ultimate', 'void'], [1, 1, 1, 1, 1, 1, 1, 1]),
        ];

        // I will continue adding up to 72 in chunks to be thorough.
        
        foreach ($vocations as $vocation) {
            DB::table('vocation_definitions')->updateOrInsert(
                ['id' => $vocation['id']],
                $vocation
            );
        }
    }

    private function makeVocation(string $id, string $name, int $tier, array $tags, array $motivation): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'tier' => $tier,
            'tags' => json_encode($tags),
            'motivation_profile' => json_encode([
                'creation' => $motivation[0],
                'destruction' => $motivation[1],
                'order' => $motivation[2],
                'chaos' => $motivation[3],
                'self_preservation' => $motivation[4],
                'altruism' => $motivation[5],
                'physical' => $motivation[6],
                'metaphysical' => $motivation[7]
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
