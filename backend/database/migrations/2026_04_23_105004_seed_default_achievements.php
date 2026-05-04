<?php

use Illuminate\Database\Migrations\Migration;
use App\Modules\Achievement\Entities\Achievement;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $achievements = [
            [
                'code' => 'first_discovery',
                'name' => 'First Discovery',
                'description' => 'Unlock your first technology in any universe.',
                'category' => 'discovery',
                'icon' => 'lightbulb',
                'rarity' => 'common',
                'conditions' => json_encode(['technology_count' => ['op' => 'gte', 'value' => 1]]),
                'sort_order' => 1,
            ],
            [
                'code' => 'iron_age',
                'name' => 'Iron Age',
                'description' => 'Enter the Iron Age epoch.',
                'category' => 'epoch',
                'icon' => 'hammer',
                'rarity' => 'common',
                'conditions' => json_encode(['epoch' => ['op' => 'eq', 'value' => 'Iron Age']]),
                'sort_order' => 10,
            ],
            [
                'code' => 'modern_era',
                'name' => 'Modern Era',
                'description' => 'Enter the Modern Era epoch.',
                'category' => 'epoch',
                'icon' => 'factory',
                'rarity' => 'rare',
                'conditions' => json_encode(['epoch' => ['op' => 'eq', 'value' => 'Modern Era']]),
                'sort_order' => 20,
            ],
            [
                'code' => 'scar_survivor',
                'name' => 'Scar Survivor',
                'description' => 'Survive a world-scarring event.',
                'category' => 'survival',
                'icon' => 'shield-alert',
                'rarity' => 'uncommon',
                'conditions' => json_encode(['scar_count' => ['op' => 'gte', 'value' => 1]]),
                'sort_order' => 30,
            ],
            [
                'code' => 'hero_rises',
                'name' => 'Hero Rises',
                'description' => 'Witness the rise of a celebrity hero.',
                'category' => 'celebrity',
                'icon' => 'crown',
                'rarity' => 'epic',
                'conditions' => json_encode(['is_celebrity' => ['op' => 'eq', 'value' => true]]),
                'sort_order' => 40,
            ],
            [
                'code' => 'golden_age',
                'name' => 'Golden Age',
                'description' => 'Achieve 100+ actors in a single universe.',
                'category' => 'myth',
                'icon' => 'star',
                'rarity' => 'legendary',
                'conditions' => json_encode(['actor_count' => ['op' => 'gte', 'value' => 100]]),
                'sort_order' => 50,
            ],
        ];

        foreach ($achievements as $data) {
            Achievement::updateOrCreate(['code' => $data['code']], $data);
        }
    }

    public function down(): void
    {
        Achievement::whereIn('code', [
            'first_discovery', 'iron_age', 'modern_era', 'scar_survivor', 'hero_rises', 'golden_age',
        ])->delete();
    }
};
