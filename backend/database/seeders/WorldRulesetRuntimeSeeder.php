<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorldRulesetRuntimeSeeder extends Seeder
{
    public function run(): void
    {
        // Get all worlds
        $worlds = DB::table('worlds')->get();

        foreach ($worlds as $world) {
            DB::table('world_ruleset_runtime')->updateOrInsert(
                ['world_id' => $world->id],
                [
                    'ruleset_id' => 'realistic_modern',
                    'active_tick' => 0,
                    'ambient_energy' => json_encode(["status" => "stable", "flux" => 0.0]),
                    'reality_stability' => 1.0,
                    'dynamic_axioms' => json_encode([]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
