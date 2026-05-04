<?php

namespace App\Modules\Simulation\Services\Narrative;

use App\Modules\Narrative\Models\Chronicle;
use App\Modules\World\Models\Universe;
use Illuminate\Support\Collection;

/**
 * SagaBuilderService: The Historian of WorldOS.
 * Aggregates individual Chronicles into high-level WorldLegends and WorldSagas.
 */
class SagaBuilderService
{
    /**
     * Build active sagas for a universe at a specific tick.
     */
    public function buildActiveSagas(int $universeId, int $tick): array
    {
        // 1. Fetch recent chronicles (last 1000 ticks)
        $recentChronicles = Chronicle::where('universe_id', $universeId)
            ->where('from_tick', '>', $tick - 1000)
            ->get();

        $sagas = [];

        // 2. Identify "Dark Ages" (High trauma density)
        $traumaCount = $recentChronicles->where('type', 'TRAUMA')->count();
        if ($traumaCount > 50) {
            $sagas[] = [
                'id' => 1,
                'name' => "The Weeping Era",
                'theme' => "CATASTROPHE",
                'legends' => $this->mapToLegends($recentChronicles->where('type', 'TRAUMA')->take(5)),
            ];
        }

        // 3. Identify "Golden Ages" (High wealth/prosperity)
        $wealthCount = $recentChronicles->where('type', 'PROSPERITY')->count();
        if ($wealthCount > 30) {
            $sagas[] = [
                'id' => 2,
                'name' => "The Gilded Age",
                'theme' => "GOLDEN_AGE",
                'legends' => $this->mapToLegends($recentChronicles->where('type', 'PROSPERITY')->take(5)),
            ];
        }

        return $sagas;
    }

    /**
     * Map Raw Chronicles to Proto-compatible Legend format.
     */
    private function mapToLegends(Collection $chronicles): array
    {
        return $chronicles->map(fn($c) => [
            'id' => $c->id,
            'category' => $c->type,
            'title' => substr($c->content, 0, 50),
            'description' => $c->content,
            'tick_start' => $c->from_tick,
            'tick_end' => $c->to_tick,
            'importance' => $c->importance,
            'involved_actor_ids' => [$c->actor_id],
        ])->values()->all();
    }
}

