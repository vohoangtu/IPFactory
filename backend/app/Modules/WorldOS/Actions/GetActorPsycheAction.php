<?php

declare(strict_types=1);

namespace App\Modules\WorldOS\Actions;

use App\Contracts\ActionInterface;
use App\Contracts\ActorPsycheProjectorInterface;
use App\Modules\Intelligence\Models\Actor;
use App\Modules\Intelligence\Models\AgentDecision;

class GetActorPsycheAction implements ActionInterface
{
    private const DECISION_LIMIT = 10;

    public function __construct(private readonly ActorPsycheProjectorInterface $psycheProjector)
    {
    }

    /** @return array{data: array<string, mixed>} */
    public function handle(int $actorId): array
    {
        $actor = Actor::query()->findOrFail($actorId);
        $metrics = is_array($actor->metrics) ? $actor->metrics : [];
        $psychState = is_array($metrics['psych_state'] ?? null) ? $metrics['psych_state'] : [];
        $psyche = $this->psycheProjector->project($psychState);

        $decisions = AgentDecision::query()
            ->where('actor_id', $actorId)
            ->orderByDesc('tick')
            ->orderByDesc('id')
            ->limit(self::DECISION_LIMIT)
            ->get()
            ->map(fn (AgentDecision $d): array => [
                'id' => $d->id,
                'tick' => (int) $d->tick,
                'action_type' => $d->action_type,
                'reasoning' => $d->reasoning,
                'utility_score' => $d->utility_score !== null ? (float) $d->utility_score : null,
                'confidence' => $d->confidence !== null ? (float) $d->confidence : null,
                'impact' => $d->impact,
            ])
            ->all();

        return [
            'data' => [
                'actor' => [
                    'id' => $actor->id,
                    'universe_id' => (int) $actor->universe_id,
                    'name' => $actor->name,
                    'archetype' => $actor->archetype,
                    'is_alive' => (bool) $actor->is_alive,
                    'life_stage' => $actor->life_stage,
                ],
                'emotions' => $psyche['emotions'],
                'needs' => $psyche['needs'],
                'goals' => $psyche['goals'],
                'trait_vector' => is_array($metrics['trait_vector'] ?? null) ? array_values($metrics['trait_vector']) : [],
                'recent_decisions' => $decisions,
            ],
        ];
    }
}
