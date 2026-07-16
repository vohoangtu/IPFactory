<?php

declare(strict_types=1);

namespace App\Modules\WorldOS\Actions;

use App\Contracts\ActionInterface;
use App\Modules\World\Models\Universe;

class GetUniverseCivilizationAction implements ActionInterface
{
    /** @return array{data: array<string, mixed>} */
    public function handle(int $universeId): array
    {
        $universe = Universe::query()->with('latestSnapshot')->findOrFail($universeId);
        $snapshot = $universe->latestSnapshot;

        return [
            'data' => [
                'universe_id' => $universe->id,
                'status' => $universe->status,
                'current_tick' => (int) $universe->current_tick,
                'epoch' => $universe->epoch !== null ? (int) $universe->epoch : null,
                'metrics' => [
                    'entropy' => $universe->entropy !== null ? (float) $universe->entropy : null,
                    'stability_index' => $snapshot?->stability_index !== null ? (float) $snapshot->stability_index : null,
                    'structural_coherence' => $universe->structural_coherence !== null ? (float) $universe->structural_coherence : null,
                    'fitness_score' => $universe->fitness_score !== null ? (float) $universe->fitness_score : null,
                ],
                'complexity' => [
                    'actor_count' => $universe->actors()->count(),
                    'living_actor_count' => $universe->actors()->where('is_alive', true)->count(),
                    'supreme_entity_count' => $universe->supremeEntities()->count(),
                ],
                'snapshot' => $snapshot !== null ? [
                    'tick' => (int) $snapshot->tick,
                    'metrics' => is_array($snapshot->metrics) ? $snapshot->metrics : [],
                ] : null,
            ],
        ];
    }
}
