<?php

namespace App\Modules\Simulation\Services\Narrative;

use App\Modules\Simulation\Models\BranchEvent;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Collection;

/**
 * Civilization Memory Engine (Phase E): aggregates key events and civilization state
 * for a universe over a tick range. Used by narrative, mythology, or decision systems.
 */
class CivilizationMemoryEngine
{
    public function __construct() {}

    /**
     * Build structured civilization memory for the universe in the given tick range.
     *
     * @return array{key_events: array, branch_events: array, chronicles: array, collapse_hints: array}
     */
    public function getMemory(Universe $universe, ?int $fromTick = null, ?int $toTick = null): array
    {
        $fromTick = $fromTick ?? 0;
        if ($toTick === null) {
            $latest = $universe->snapshots()->orderByDesc('tick')->first();
            $toTick = $latest ? (int) $latest->tick : (int) ($universe->current_tick ?? 0);
        }

        $maxEvents = (int) config('worldos.civilization_memory.max_events', 50);
        $maxChronicles = (int) config('worldos.civilization_memory.max_chronicles', 30);

        $branchEvents = $this->loadBranchEvents($universe->id, $fromTick, $toTick, $maxEvents);
        $chronicles = $this->loadChronicles($universe->id, $fromTick, $toTick, $maxChronicles);

        $keyEvents = $this->buildKeyEvents($branchEvents, $chronicles, $maxEvents);
        $collapseHints = $this->extractCollapseHints($chronicles);

        return [
            'universe_id' => $universe->id,
            'from_tick' => $fromTick,
            'to_tick' => $toTick,
            'key_events' => $keyEvents,
            'branch_events' => $branchEvents->map(fn ($e) => [
                'from_tick' => $e->from_tick,
                'event_type' => $e->event_type,
                'payload' => $e->payload,
            ])->values()->all(),
            'chronicles' => $chronicles->map(fn ($c) => [
                'id' => $c->id,
                'from_tick' => $c->from_tick,
                'to_tick' => $c->to_tick,
                'type' => $c->type,
                'content_preview' => mb_substr((string) $c->content, 0, 200),
            ])->values()->all(),
            'collapse_hints' => $collapseHints,
        ];
    }

    protected function loadBranchEvents(int $universeId, int $fromTick, int $toTick, int $limit): Collection
    {
        return BranchEvent::where('universe_id', $universeId)
            ->where('from_tick', '>=', $fromTick)
            ->where('from_tick', '<=', $toTick)
            ->orderBy('from_tick')
            ->limit($limit)
            ->get();
    }

    protected function loadChronicles(int $universeId, int $fromTick, int $toTick, int $limit): Collection
    {
        return Chronicle::where('universe_id', $universeId)
            ->where(function ($q) use ($fromTick, $toTick) {
                $q->whereBetween('from_tick', [$fromTick, $toTick])
                    ->orWhereBetween('to_tick', [$fromTick, $toTick])
                    ->orWhere(function ($q2) use ($fromTick, $toTick) {
                        $q2->where('from_tick', '<=', $fromTick)->where('to_tick', '>=', $toTick);
                    });
            })
            ->orderBy('from_tick')
            ->limit($limit)
            ->get();
    }

    protected function buildKeyEvents(Collection $branchEvents, Collection $chronicles, int $maxEvents): array
    {
        $events = [];

        foreach ($branchEvents as $e) {
            $events[] = [
                'tick' => (int) $e->from_tick,
                'source' => 'branch',
                'type' => $e->event_type,
                'summary' => $e->event_type === 'fork' ? 'Universe fork' : ($e->event_type ?? 'branch_event'),
            ];
        }

        foreach ($chronicles as $c) {
            $events[] = [
                'tick' => (int) $c->from_tick,
                'to_tick' => (int) $c->to_tick,
                'source' => 'chronicle',
                'type' => $c->type ?? 'chronicle',
                'summary' => mb_substr((string) $c->content, 0, 120),
            ];
        }

        usort($events, fn ($a, $b) => ($a['tick'] ?? 0) <=> ($b['tick'] ?? 0));
        return array_slice(array_values($events), 0, $maxEvents);
    }

    protected function extractCollapseHints(Collection $chronicles): array
    {
        $hints = [];
        $collapseKeywords = ['collapse', 'sụp đổ', 'crisis', 'khủng hoảng', 'bi kịch', 'tận thế'];
        foreach ($chronicles as $c) {
            $content = (string) ($c->content ?? '');
            $type = (string) ($c->type ?? '');
            if ($type === 'collapse' || $type === 'crisis') {
                $hints[] = ['chronicle_id' => $c->id, 'from_tick' => $c->from_tick, 'to_tick' => $c->to_tick, 'reason' => 'type'];
                continue;
            }
            foreach ($collapseKeywords as $kw) {
                if (mb_stripos($content, $kw) !== false) {
                    $hints[] = ['chronicle_id' => $c->id, 'from_tick' => $c->from_tick, 'to_tick' => $c->to_tick, 'reason' => 'content'];
                    break;
                }
            }
        }
        return $hints;
    }
}

