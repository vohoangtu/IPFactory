<?php

namespace App\Modules\Simulation\Core\Engines\Meta;

use App\Modules\Narrative\Models\Chronicle;
use App\Modules\World\Models\Era;
use App\Modules\Narrative\Models\Myth;
use App\Modules\World\Models\Religion;
use App\Modules\World\Models\Universe;

/**
 * History Engine (Tier 13).
 * Aggregates Chronicle events into a queryable timeline: collapse events, phase transitions,
 * settlements, wars, civilization rise/fall. For API/dashboard.
 */
class HistoryEngine
{
    public function getTimeline(Universe $universe, ?int $limit = null): array
    {
        $limit = $limit ?? (int) config('worldos.intelligence.history_timeline_limit', 100);
        $chronicles = Chronicle::query()
            ->where('universe_id', $universe->id)
            ->orderByDesc('from_tick')
            ->limit($limit)
            ->get();

        $timeline = [];
        foreach ($chronicles as $c) {
            $timeline[] = [
                'from_tick' => $c->from_tick,
                'to_tick' => $c->to_tick,
                'type' => $c->type,
                'content' => $c->content,
                'actor_id' => $c->actor_id,
                'importance' => $c->importance,
                'payload' => $c->raw_payload ?? [],
            ];
        }
        return array_reverse($timeline);
    }

    /**
     * Phase 6: Top events by importance (narrative gravity).
     */
    public function getTopEventsByImportance(Universe $universe, int $limit = 50): array
    {
        $chronicles = Chronicle::query()
            ->where('universe_id', $universe->id)
            ->whereNotNull('importance')
            ->orderByDesc('importance')
            ->limit($limit)
            ->get();

        $timeline = [];
        foreach ($chronicles as $c) {
            $timeline[] = [
                'from_tick' => $c->from_tick,
                'to_tick' => $c->to_tick,
                'type' => $c->type,
                'content' => $c->content,
                'actor_id' => $c->actor_id,
                'importance' => $c->importance,
                'payload' => $c->raw_payload ?? [],
            ];
        }
        return $timeline;
    }

    /**
     * Phase 6: Events for a specific actor.
     */
    public function getEventsForActor(int $actorId, int $limit = 100): array
    {
        $chronicles = Chronicle::query()
            ->where('actor_id', $actorId)
            ->orderByDesc('from_tick')
            ->limit($limit)
            ->get();

        $timeline = [];
        foreach ($chronicles as $c) {
            $timeline[] = [
                'from_tick' => $c->from_tick,
                'to_tick' => $c->to_tick,
                'type' => $c->type,
                'content' => $c->content,
                'importance' => $c->importance,
                'payload' => $c->raw_payload ?? [],
            ];
        }
        return $timeline;
    }

    /**
     * Group timeline entries by type for dashboard (collapse, phase_transition, civilization_collapse, etc.).
     */
    public function getTimelineByType(Universe $universe, ?int $limit = null): array
    {
        $timeline = $this->getTimeline($universe, $limit);
        $byType = [];
        foreach ($timeline as $entry) {
            $type = $entry['type'] ?? 'other';
            if (!isset($byType[$type])) {
                $byType[$type] = [];
            }
            $byType[$type][] = $entry;
        }
        return $byType;
    }

    /**
     * Build a compact historical spine that higher-level APIs can expose.
     *
     * @return array<string, mixed>
     */
    public function getHistoricalSpine(Universe $universe): array
    {
        $timeline = $this->getTimeline($universe, 200);
        $topEvents = $this->getTopEventsByImportance($universe, 12);
        $myths = Myth::query()
            ->where('universe_id', $universe->id)
            ->orderByDesc('impact')
            ->limit(5)
            ->get();
        $religions = Religion::query()
            ->where('universe_id', $universe->id)
            ->orderByDesc('followers')
            ->limit(3)
            ->get();

        $founding = collect($timeline)->first();
        $goldenAge = collect($topEvents)->first(fn (array $event) => !in_array(($event['type'] ?? ''), ['collapse', 'crisis'], true));
        $crisis = collect($timeline)->reverse()->first(fn (array $event) => in_array(($event['type'] ?? ''), ['collapse', 'crisis', 'material_transition'], true));

        return [
            'timeline_count' => count($timeline),
            'top_event_count' => count($topEvents),
            'founding_event' => $founding ? [
                'tick' => (int) ($founding['from_tick'] ?? 0),
                'type' => $founding['type'] ?? null,
                'summary' => $founding['content'] ?? null,
            ] : null,
            'golden_age_hint' => $goldenAge ? [
                'tick' => (int) ($goldenAge['from_tick'] ?? 0),
                'type' => $goldenAge['type'] ?? null,
                'summary' => $goldenAge['content'] ?? null,
            ] : null,
            'crisis_hint' => $crisis ? [
                'tick' => (int) ($crisis['from_tick'] ?? 0),
                'type' => $crisis['type'] ?? null,
                'summary' => $crisis['content'] ?? null,
            ] : null,
            'dominant_myth' => $myths->first() ? [
                'id' => $myths->first()->id,
                'type' => $myths->first()->myth_type,
                'impact' => (float) $myths->first()->impact,
            ] : null,
            'dominant_religion' => $religions->first() ? [
                'id' => $religions->first()->id,
                'name' => $religions->first()->name,
                'followers' => (int) $religions->first()->followers,
            ] : null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getEraSummaries(Universe $universe, int $limit = 6): array
    {
        $eras = Era::query()
            ->where('universe_id', $universe->id)
            ->orderBy('start_tick')
            ->limit($limit)
            ->get();

        if ($eras->isEmpty()) {
            return $this->buildSyntheticEraSummaries($universe, $limit);
        }

        return $eras->map(function (Era $era): array {
            $dominantTypes = Chronicle::query()
                ->where('universe_id', $era->universe_id)
                ->whereBetween('from_tick', [(int) $era->start_tick, (int) $era->end_tick])
                ->selectRaw('type, COUNT(*) as total')
                ->groupBy('type')
                ->orderByDesc('total')
                ->limit(3)
                ->get()
                ->map(fn ($row) => ['type' => $row->type, 'count' => (int) $row->total])
                ->values()
                ->all();

            return [
                'title' => $era->title,
                'summary' => $era->summary,
                'start_tick' => (int) $era->start_tick,
                'end_tick' => (int) $era->end_tick,
                'dominant_types' => $dominantTypes,
            ];
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildSyntheticEraSummaries(Universe $universe, int $limit): array
    {
        $chronicles = Chronicle::query()
            ->where('universe_id', $universe->id)
            ->orderBy('from_tick')
            ->get(['from_tick', 'to_tick', 'type', 'content']);

        if ($chronicles->isEmpty()) {
            return [];
        }

        $minTick = (int) ($chronicles->min('from_tick') ?? 0);
        $maxTick = (int) ($chronicles->max('to_tick') ?? $minTick);
        $span = max(1, $maxTick - $minTick + 1);
        $window = max(1, (int) ceil($span / max(1, $limit)));
        $summaries = [];

        for ($start = $minTick; $start <= $maxTick; $start += $window) {
            $end = min($maxTick, $start + $window - 1);
            $slice = $chronicles->filter(fn (Chronicle $chronicle) => (int) $chronicle->from_tick >= $start && (int) $chronicle->from_tick <= $end);
            if ($slice->isEmpty()) {
                continue;
            }

            $typeCounts = $slice->groupBy('type')->map->count()->sortDesc();
            $dominantType = (string) ($typeCounts->keys()->first() ?? 'chronicle');
            $headline = (string) ($slice->sortByDesc('to_tick')->first()->content ?? '');

            $summaries[] = [
                'title' => ucfirst(str_replace('_', ' ', $dominantType)) . " phase",
                'summary' => mb_substr($headline, 0, 220),
                'start_tick' => $start,
                'end_tick' => $end,
                'dominant_types' => $typeCounts->take(3)->map(fn ($count, $type) => [
                    'type' => (string) $type,
                    'count' => (int) $count,
                ])->values()->all(),
            ];
        }

        return $summaries;
    }
}
