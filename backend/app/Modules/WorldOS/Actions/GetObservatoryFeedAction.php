<?php

declare(strict_types=1);

namespace App\Modules\WorldOS\Actions;

use App\Contracts\ActionInterface;
use App\Modules\Narrative\Models\Chronicle;
use Illuminate\Support\Facades\DB;

class GetObservatoryFeedAction implements ActionInterface
{
    private const DEFAULT_LIMIT = 50;

    /**
     * @param array{after_tick?: int|null, before_tick?: int|null, types?: string[]|null, limit?: int|null} $filters
     * @return array{data: array<int, array<string, mixed>>, meta: array{count: int, next_before_tick: int|null}}
     */
    public function handle(int $universeId, array $filters = []): array
    {
        $limit = (int) ($filters['limit'] ?? self::DEFAULT_LIMIT);
        $afterTick = isset($filters['after_tick']) ? (int) $filters['after_tick'] : null;
        $beforeTick = isset($filters['before_tick']) ? (int) $filters['before_tick'] : null;
        $types = $filters['types'] ?? null;

        $events = DB::table('world_events')
            ->where('universe_id', $universeId)
            ->when($afterTick !== null, fn ($q) => $q->where('tick', '>', $afterTick))
            ->when($beforeTick !== null, fn ($q) => $q->where('tick', '<', $beforeTick))
            ->when($types !== null, fn ($q) => $q->whereIn('type', $types))
            ->orderByDesc('tick')
            ->limit($limit)
            ->get()
            ->map(function (object $row): array {
                $payload = json_decode($row->payload ?? '{}', true) ?: [];

                return [
                    'id' => (string) $row->id,
                    'kind' => 'event',
                    'type' => $row->type,
                    'tick' => (int) $row->tick,
                    'severity' => $payload['severity'] ?? 'info',
                    'occurred_at' => $payload['occurred_at'] ?? (string) $row->created_at,
                    'payload' => $payload['data'] ?? $payload,
                ];
            });

        $chronicles = collect();
        if ($types === null || in_array('chronicle', $types, true)) {
            $chronicles = Chronicle::query()
                ->where('universe_id', $universeId)
                ->when($afterTick !== null, fn ($q) => $q->whereRaw('COALESCE(to_tick, from_tick) > ?', [$afterTick]))
                ->when($beforeTick !== null, fn ($q) => $q->whereRaw('COALESCE(to_tick, from_tick) < ?', [$beforeTick]))
                ->orderByRaw('COALESCE(to_tick, from_tick) DESC')
                ->limit($limit)
                ->get()
                ->map(fn (Chronicle $c): array => [
                    'id' => 'chronicle-' . $c->id,
                    'kind' => 'chronicle',
                    'type' => 'chronicle',
                    'tick' => (int) ($c->to_tick ?? $c->from_tick ?? 0),
                    'severity' => 'notable',
                    'occurred_at' => $c->created_at?->toIso8601String(),
                    'payload' => [
                        'chronicle_id' => $c->id,
                        'chronicle_type' => $c->type,
                        'importance' => $c->importance,
                        'content' => $c->content,
                        'has_animation' => ! empty($c->animation_script) || ! empty($c->raw_payload['animation_script'] ?? null),
                    ],
                ]);
        }

        $items = $events->concat($chronicles)->sortByDesc('tick')->values()->take($limit);
        $oldest = $items->last();

        return [
            'data' => $items->all(),
            'meta' => [
                'count' => $items->count(),
                'next_before_tick' => $oldest['tick'] ?? null,
            ],
        ];
    }
}
