<?php

namespace Database\Factories;

use App\Modules\World\Models\Universe;
use App\Modules\World\Models\World;
use Illuminate\Database\Eloquent\Factories\Factory;

class UniverseFactory extends Factory
{
    protected $model = Universe::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'world_id' => World::factory(),
            'current_tick' => 0,
            'entropy' => 0.5,
            'stability_index' => 0.5,
            'status' => 'active',
            'state_vector' => [],
            'observation_load' => 0.0,
            'structural_coherence' => 1.0,
        ];
    }
}
