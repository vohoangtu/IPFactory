<?php

namespace Database\Factories;

use App\Modules\World\Models\World;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class WorldFactory extends Factory
{
    protected $model = World::class;

    public function definition(): array
    {
        // worlds.primary_ruleset_id defaults to 'realistic_modern' with an FK
        // to ruleset_definitions, which only RuleSetDefinitionSeeder populates —
        // ensure the row exists so factory-created worlds don't violate the FK.
        DB::table('ruleset_definitions')->insertOrIgnore([
            'id' => 'realistic_modern',
            'name' => 'Realistic Modern (test stub)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'name' => $this->faker->company,
            'slug' => $this->faker->slug,
            'world_seed' => ['test' => 123],
            'global_tick' => 0,
            'is_autonomic' => true,
        ];
    }
}
