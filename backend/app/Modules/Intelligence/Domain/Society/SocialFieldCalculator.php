<?php

namespace App\Modules\Intelligence\Domain\Society;

use App\Modules\Intelligence\Entities\ActorState;

class SocialFieldCalculator
{
    /**
     * Calculates the mean-field aggregation of population traits. O(n).
     * 
     * @param array<ActorState> $actors
     * @return SocialField
     */
    public function calculate(array $actors): SocialField
    {
        if (empty($actors)) {
            return new SocialField();
        }

        $sums = [
            'survival'     => 0.0,
            'reproduction' => 0.0,
            'wealth'       => 0.0,
            'power'        => 0.0,
            'knowledge'    => 0.0,
            'meaning'      => 0.0,
            'status'       => 0.0,
            'belonging'    => 0.0,
        ];

        foreach ($actors as $actor) {
            $sums['survival']     += ($actor->traits['Resilience'] ?? 0.5);
            $sums['reproduction'] += ($actor->traits['Vitality'] ?? 0.5);
            $sums['wealth']       += ($actor->traits['Pragmatism'] ?? 0.5) * 0.7 + ($actor->traits['Ambition'] ?? 0.5) * 0.3;
            $sums['power']        += ($actor->traits['Dominance'] ?? 0.5) * 0.6 + ($actor->traits['Coercion'] ?? 0.5) * 0.4;
            $sums['knowledge']    += ($actor->traits['Curiosity'] ?? 0.5);
            $sums['meaning']      += ($actor->traits['Hope'] ?? 0.5) * 0.7 + (1 - ($actor->traits['Dogmatism'] ?? 0.5)) * 0.3;
            $sums['status']       += ($actor->traits['Pride'] ?? 0.5) * 0.8 + ($actor->traits['Dominance'] ?? 0.5) * 0.2;
            $sums['belonging']    += ($actor->traits['Solidarity'] ?? 0.5) * 0.4 + ($actor->traits['Conformity'] ?? 0.5) * 0.3 + ($actor->traits['Loyalty'] ?? 0.3);
        }

        $count = count($actors);

        return new SocialField(
            $sums['survival'] / $count,
            $sums['reproduction'] / $count,
            $sums['wealth'] / $count,
            $sums['power'] / $count,
            $sums['knowledge'] / $count,
            $sums['meaning'] / $count,
            $sums['status'] / $count,
            $sums['belonging'] / $count
        );
    }
}
