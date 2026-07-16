<?php

declare(strict_types=1);

namespace App\Modules\Psychology\Services;

use App\Contracts\ActorPsycheProjectorInterface;
use App\Modules\Psychology\ValueObjects\PsychologicalState;

class ActorPsycheProjector implements ActorPsycheProjectorInterface
{
    public function __construct(private readonly GoalGenerator $goalGenerator)
    {
    }

    public function project(array $psychState): array
    {
        $state = PsychologicalState::fromArray($psychState);

        return [
            'emotions' => $state->toArray(),
            'needs' => array_map(fn (float $v): float => round($v, 3), $this->goalGenerator->computeNeeds($state)),
            'goals' => $this->goalGenerator->generate($state),
        ];
    }
}
