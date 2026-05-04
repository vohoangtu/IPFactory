<?php

namespace App\Modules\Intelligence\Services;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Models\Actor;
use App\Modules\Intelligence\Entities\ActorState;
use Illuminate\Support\Facades\Log;

/**
 * Service to calculate the "Collective Unconscious" (8D Motivation Vector) for a universe.
 * Phase 80: Mind Layer - RULE_ATTRACTION.
 */
class CollectiveUnconsciousService
{
    /**
     * Calculates the average motivation profile for all alive actors in a universe.
     * 
     * @param Universe $universe
     * @return array 8D Motivation Vector
     */
    public function calculate(Universe $universe): array
    {
        $aliveActors = Actor::where('universe_id', $universe->id)
            ->where('is_alive', true)
            ->get();

        if ($aliveActors->isEmpty()) {
            return $this->defaultVector();
        }

        $sums = $this->zeroVector();
        $totalWeight = 0;

        foreach ($aliveActors as $actor) {
            // Convert Eloquent model to DTO to use getMotivationProfile()
            $state = $this->mapToState($actor);
            $profile = $state->getMotivationProfile();
            
            // Influence-weighted averaging (higher influence actors pull the collective more)
            $weight = max(1.0, $state->getInfluence() * 10); 
            $totalWeight += $weight;

            foreach ($profile as $dim => $value) {
                $sums[$dim] += $value * $weight;
            }
        }

        $result = [];
        foreach ($sums as $dim => $sum) {
            $result[$dim] = round($sum / $totalWeight, 4);
        }

        return $result;
    }

    private function mapToState(Actor $actor): ActorState
    {
        return new ActorState(
            id: $actor->id,
            universeId: $actor->universe_id,
            name: $actor->name,
            archetype: $actor->archetype ?? 'Unknown',
            traits: $actor->traits ?? [],
            metrics: $actor->metrics ?? [],
            isAlive: $actor->is_alive,
            generation: $actor->generation,
            biography: $actor->biography,
            isHeroic: $actor->is_heroic,
            heroicType: $actor->heroic_type
        );
    }

    private function zeroVector(): array
    {
        return [
            'survival'     => 0.0,
            'reproduction' => 0.0,
            'wealth'       => 0.0,
            'power'        => 0.0,
            'knowledge'    => 0.0,
            'meaning'      => 0.0,
            'status'       => 0.0,
            'belonging'    => 0.0,
        ];
    }

    private function defaultVector(): array
    {
        return [
            'survival'     => 0.5,
            'reproduction' => 0.5,
            'wealth'       => 0.5,
            'power'        => 0.5,
            'knowledge'    => 0.5,
            'meaning'      => 0.5,
            'status'       => 0.5,
            'belonging'    => 0.5,
        ];
    }
}

