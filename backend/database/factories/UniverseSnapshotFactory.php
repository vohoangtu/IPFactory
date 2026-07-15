<?php

namespace Database\Factories;

use App\Modules\Simulation\Models\UniverseSnapshot;
use App\Modules\World\Models\Universe;
use Illuminate\Database\Eloquent\Factories\Factory;

class UniverseSnapshotFactory extends Factory
{
    protected $model = UniverseSnapshot::class;

    public function definition(): array
    {
        return [
            'universe_id' => Universe::factory(),
            'tick' => 0,
            'state_vector' => [],
            'entropy' => 0.5,
            'stability_index' => 0.5,
            'metrics' => [],
        ];
    }
}
