<?php

namespace Database\Factories;

use App\Modules\World\Models\World;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorldFactory extends Factory
{
    protected $model = World::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'slug' => $this->faker->slug,
            'world_seed' => ['test' => 123],
            'global_tick' => 0,
            'is_autonomic' => true,
        ];
    }
}
