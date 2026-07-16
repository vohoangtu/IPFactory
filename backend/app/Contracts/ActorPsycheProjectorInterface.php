<?php

declare(strict_types=1);

namespace App\Contracts;

interface ActorPsycheProjectorInterface
{
    /**
     * Chiếu psych_state đã persist (actors.metrics['psych_state']) thành read-model tâm lý.
     *
     * @param  array<string, mixed>  $psychState
     * @return array{emotions: array<string, float>, needs: array<string, float>, goals: array<int, array{type: string, priority: float}>}
     */
    public function project(array $psychState): array;
}
