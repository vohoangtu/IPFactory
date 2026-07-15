<?php

declare(strict_types=1);

namespace App\Modules\WorldOS\Actions;

use App\Contracts\ActionInterface;
use App\Modules\Narrative\Models\Chronicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
        $includeChronicles = $types === null || in_array('chronicle', $types, true);

        // Fetch one extra row per source so we can detect whether the page boundary
        // falls mid-tick (in which case we must fetch the rest of that tick separately).
        $events = DB::table('world_events')
            ->where('universe_id', $universeId)
            ->when($afterTick !== null, fn ($q) => $q->where('tick', '>', $afterTick))
            ->when($beforeTick !== null, fn ($q) => $q->where('tick', '<', $beforeTick))
            ->when($types !== null, fn ($q) => $q->whereIn('type', $types))
            ->orderByDesc('tick')
            ->limit($limit + 1)
            ->get()
            ->map(fn (object $row): array => $this->mapEventRow($row));

        $chronicles = collect();
        if ($includeChronicles) {
            $chronicles = Chronicle::query()
                ->where('universe_id', $universeId)
                ->when($afterTick !== null, fn ($q) => $q->whereRaw('COALESCE(to_tick, from_tick) > ?', [$afterTick]))
                ->when($beforeTick !== null, fn ($q) => $q->whereRaw('COALESCE(to_tick, from_tick) < ?', [$beforeTick]))
                ->orderByRaw('COALESCE(to_tick, from_tick) DESC')
                ->limit($limit + 1)
                ->get()
                ->map(fn (Chronicle $c): array => $this->mapChronicle($c));
        }

        $merged = $events->concat($chronicles)->sortByDesc('tick')->values();

        if ($merged->count() <= $limit) {
            return [
                'data' => $merged->all(),
                'meta' => [
                    'count' => $merged->count(),
                    'next_before_tick' => null,
                ],
            ];
        }

        $boundary = (int) $merged[$limit - 1]['tick'];

        $page = $merged->filter(fn (array $item): bool => $item['tick'] > $boundary)->values();
        $seenIds = $page->pluck('id')->all();

        $this->appendUnseen($page, $seenIds, $merged->filter(fn (array $item): bool => $item['tick'] === $boundary));

        // Fetch the complete set of boundary-tick items — the limit+1 window above may
        // have truncated some of them when more than one source shares the boundary tick.
        $boundaryEvents = DB::table('world_events')
            ->where('universe_id', $universeId)
            ->where('tick', $boundary)
            ->when($types !== null, fn ($q) => $q->whereIn('type', $types))
            ->get()
            ->map(fn (object $row): array => $this->mapEventRow($row));

        $this->appendUnseen($page, $seenIds, $boundaryEvents);

        if ($includeChronicles) {
            $boundaryChronicles = Chronicle::query()
                ->where('universe_id', $universeId)
                ->whereRaw('COALESCE(to_tick, from_tick) = ?', [$boundary])
                ->get()
                ->map(fn (Chronicle $c): array => $this->mapChronicle($c));

            $this->appendUnseen($page, $seenIds, $boundaryChronicles);
        }

        $page = $page->sortByDesc('tick')->values();

        return [
            'data' => $page->all(),
            'meta' => [
                'count' => $page->count(),
                'next_before_tick' => $boundary,
            ],
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $page
     * @param  string[]  $seenIds
     * @param  iterable<array<string, mixed>>  $candidates
     */
    private function appendUnseen(Collection $page, array &$seenIds, iterable $candidates): void
    {
        foreach ($candidates as $item) {
            if (in_array($item['id'], $seenIds, true)) {
                continue;
            }

            $page->push($item);
            $seenIds[] = $item['id'];
        }
    }

    /** @return array<string, mixed> */
    private function mapEventRow(object $row): array
    {
        $payload = json_decode($row->payload ?? '{}', true) ?: [];

        return [
            'id' => (string) $row->id,
            'kind' => 'event',
            'type' => $row->type,
            'tick' => (int) $row->tick,
            'universe_id' => (int) $row->universe_id,
            'severity' => $payload['severity'] ?? 'info',
            'occurred_at' => $payload['occurred_at'] ?? Carbon::parse($row->created_at)->toIso8601String(),
            'payload' => $payload['data'] ?? $payload,
        ];
    }

    /** @return array<string, mixed> */
    private function mapChronicle(Chronicle $c): array
    {
        return [
            'id' => 'chronicle-' . $c->id,
            'kind' => 'chronicle',
            'type' => 'chronicle',
            'tick' => (int) ($c->to_tick ?? $c->from_tick ?? 0),
            'universe_id' => (int) $c->universe_id,
            'severity' => 'notable',
            'occurred_at' => $c->created_at?->toIso8601String(),
            'payload' => [
                'chronicle_id' => $c->id,
                'chronicle_type' => $c->type,
                'importance' => $c->importance,
                'content' => $c->content,
                'has_animation' => ! empty($c->animation_script) || ! empty($c->raw_payload['animation_script'] ?? null),
            ],
        ];
    }
}
