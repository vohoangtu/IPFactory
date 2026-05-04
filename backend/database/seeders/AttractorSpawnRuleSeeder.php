<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttractorSpawnRuleSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['parent_type' => 'revolution', 'child_type' => 'fragmentation', 'probability' => 0.3],
            ['parent_type' => 'fragmentation', 'child_type' => 'revolution', 'probability' => 0.2],
        ];

        foreach ($data as $row) {
            $now = now();
            DB::table('attractor_spawn_rules')->updateOrInsert(
                [
                    'parent_type' => $row['parent_type'],
                    'child_type' => $row['child_type'],
                ],
                [
                    'probability' => $row['probability'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
