<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SkillV1Seeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            [
                'vocation_id' => 'v_warrior',
                'name' => 'Chém Mạnh',
                'element' => json_encode(['metal' => 1.0]),
                'cost' => 10,
                'rule_dsl' => <<<DSL
rule warrior_slash
  when
    actor.mastery_level >= 1
  then
    calc damage (base_attack * 1.5) * (1.0 + element_resonance)
    emit_event SKILL_SLASH_EXECUTED
DSL
            ],
            [
                'vocation_id' => 'v_wizard',
                'name' => 'Cầu Lửa',
                'element' => json_encode(['fire' => 1.0]),
                'cost' => 20,
                'rule_dsl' => <<<DSL
rule fireball
  when
    actor.mastery_level >= 1
  then
    calc damage (base_attack * 2.5) * (1.0 + element_resonance)
    emit_event SKILL_FIREBALL_EXECUTED
    adjust_entropy 0.05
DSL
            ]
        ];

        foreach ($skills as $skill) {
            DB::table('skills')->updateOrInsert(
                ['name' => $skill['name'], 'vocation_id' => $skill['vocation_id']],
                array_merge($skill, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
