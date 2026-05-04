<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\World\Models\Belief;

class BeliefSeeder extends Seeder
{
    public function run(): void
    {
        // 1. The Void Seekers (High Awe, High Entropy, Low Pride)
        Belief::create([
            'name' => 'The Void Seekers',
            'type' => 'Religion',
            'trait_weights' => [
                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1.0, -0.5, 0.8 // Awe=0.8, Pride=-0.5
            ],
        ]);

        // 2. The Technocracy (High Logic, High Ambition)
        Belief::create([
            'name' => 'The Technocracy',
            'type' => 'Ideology',
            'trait_weights' => [
                1.0, 0.8, 0, 0, 0, 0, 0, 0, 0, 0, 0.9, 0, 0, 0, 0, 0, 0 // Logic=0.9, Ambition=0.8
            ],
        ]);

        // 3. Eternal Peace (High Empathy, Low Vengeance)
        Belief::create([
            'name' => 'Eternal Peace',
            'type' => 'Religion',
            'trait_weights' => [
                0, 0, 0, 0, 0.9, 0, 0, 0, 0, 0, 0, 0, -1.0, 0, 0, 0, 0 // Empathy=0.9, Vengeance=-1.0
            ],
        ]);
    }
}
