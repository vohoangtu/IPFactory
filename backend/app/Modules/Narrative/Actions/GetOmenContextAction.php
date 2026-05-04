<?php

namespace App\Modules\Narrative\Actions;

use App\Modules\World\Models\Universe;
use App\Modules\Intelligence\Models\Actor;
use App\Modules\Narrative\Contracts\ChronicleRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * GetOmenContextAction: Aggregates deep simulation context for the Omen Weaver AI.
 * Provides a rich dataset including world metrics, top actors, and recent history.
 */
class GetOmenContextAction
implements \App\Contracts\ActionInterface {
    public function __construct(
        protected ChronicleRepositoryInterface $chronicleRepository
    ) {}

    public function handle(int $universeId): array
    {
        $universe = Universe::with(['world', 'latestSnapshot'])->findOrFail($universeId);
        $world = $universe->world;

        // 1. Core Metrics
        $context = [
            'universe_id' => $universe->id,
            'universe_name' => $universe->name,
            'current_tick' => $universe->current_tick,
            'base_genre' => $world->base_genre ?? 'historical',
            'current_epoch' => $universe->epoch,
            'metrics' => [
                'entropy' => (float)$universe->entropy,
                'stability' => (float)$universe->structural_coherence,
            ],
            'axioms' => $universe->axioms ?? [],
        ];

        // 2. Recent History (Filter out errors)
        $chronicles = $this->chronicleRepository->findByUniverse($universeId, 15);
        $context['recent_history'] = collect($chronicles)
            ->filter(fn($c) => !str_contains($c->content ?? '', 'APIConnectionError'))
            ->map(fn($c) => [
                'tick' => $c->to_tick,
                'type' => $c->type,
                'summary' => $c->content,
                'raw_action' => $c->rawPayload['action'] ?? null,
            ])
            ->values()
            ->all();

        // 3. Top Influential Actors (Living)
        $context['top_actors'] = Actor::where('universe_id', $universeId)
            ->where('is_alive', true)
            ->orderByRaw('JSON_EXTRACT(stats, "$.power") + JSON_EXTRACT(stats, "$.wisdom") DESC')
            ->limit(5)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'archetype' => $a->archetype,
                'is_heroic' => $a->is_heroic,
                'heroic_type' => $a->heroic_type,
                'metrics' => $a->metrics,
                'traits' => array_slice($a->traits ?? [], 0, 3), // Top 3 traits
            ])
            ->all();

        // 4. Global Fields (World State)
        $context['world_fields'] = $universe->state_vector['fields'] ?? [];

        return $context;
    }
}
