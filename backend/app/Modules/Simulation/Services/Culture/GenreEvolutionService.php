<?php

namespace App\Modules\Simulation\Services\Culture;

use App\Modules\World\Models\World;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Facades\Log;

class GenreEvolutionService
{
    /**
     * Evaluate and potentially evolve the genre of the world based on its latest state.
     */
    public function evaluateEvolution(Universe $universe): void
    {
        $world = $universe->world;
        if (!$world) return;

        $tick = $universe->current_tick;
        $snapshot = $universe->snapshots()->orderBy('tick', 'desc')->first();
        if (!$snapshot) return;

        $population = $this->getTotalPopulation($snapshot);
        $currentGenre = $world->current_genre;
        $newGenre = $currentGenre;

        // Evolution Logic
        if ($currentGenre === 'historical') {
            if ($tick >= 500 || $population >= 500) {
                $newGenre = 'wuxia'; // Transition to Martial Arts era
            }
        } elseif ($currentGenre === 'wuxia') {
            if ($tick >= 2000 && $population >= 10000) {
                $newGenre = 'urban'; // Finally transition to Modern Urban
            }
        }

        if ($newGenre !== $currentGenre) {
            $world->current_genre = $newGenre;
            $world->save();

            Log::info("World Evolution: World {$world->id} evolved from {$currentGenre} to {$newGenre} at tick {$tick}");
            
            // Trigger a system event for the narrative engine to notice the change
            // Event::dispatch(new WorldEvolved($world, $currentGenre, $newGenre));
        }
    }

    /**
     * Calculate total population across all zones in the snapshot.
     */
    protected function getTotalPopulation($snapshot): float
    {
        $state = $snapshot->state_vector ?? [];
        $zones = $state['zones'] ?? [];
        $total = 0;

        foreach ($zones as $zone) {
            $total += (float) ($zone['state']['population'] ?? 0);
        }

        return $total;
    }
}
