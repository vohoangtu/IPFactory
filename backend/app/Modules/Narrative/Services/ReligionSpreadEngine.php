<?php

namespace App\Modules\Narrative\Services;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\Narrative\Models\Chronicle;
use App\Modules\Simulation\Models\Civilization;
use App\Modules\World\Models\Religion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ReligionSpreadEngine
{
    public function runForUniverse($universe, int $tick): void
    {
        $religions = Religion::query()
            ->where('universe_id', $universe->id)
            ->orderByDesc('followers')
            ->orderByDesc('spread_rate')
            ->get();

        if ($religions->isEmpty()) {
            return;
        }

        $aliveWithoutReligion = Actor::query()
            ->where('universe_id', $universe->id)
            ->where('is_alive', true)
            ->whereDoesntHave('religions')
            ->orderBy('id')
            ->get(['id']);

        if ($aliveWithoutReligion->isEmpty()) {
            return;
        }

        $cursor = 0;
        $spreadChanges = [];

        foreach ($religions as $religion) {
            $newFollowers = $this->spreadForReligion($religion, $aliveWithoutReligion, $tick, $cursor);
            if ($newFollowers > 0) {
                $religion->followers = $religion->actors()->count();
                $religion->save();
                $spreadChanges[] = ['religion' => $religion, 'new_followers' => $newFollowers];
            }
        }

        if ($spreadChanges === []) {
            return;
        }

        $dominant = collect($spreadChanges)
            ->sortByDesc(fn (array $row) => $row['religion']->followers)
            ->first();

        if ($dominant) {
            Civilization::query()
                ->where('universe_id', $universe->id)
                ->update(['dominant_religion_id' => $dominant['religion']->id]);
        }

        foreach ($spreadChanges as $change) {
            Chronicle::create([
                'universe_id' => $universe->id,
                'from_tick' => $tick,
                'to_tick' => $tick,
                'type' => 'religion_spread',
                'content' => "{$change['religion']->name} spread to {$change['new_followers']} new followers.",
                'importance' => 0.38,
                'raw_payload' => [
                    'religion_id' => $change['religion']->id,
                    'new_followers' => $change['new_followers'],
                    'total_followers' => $change['religion']->followers,
                ],
            ]);
        }

        Log::info('NarrativeLoom: religion spread processed', [
            'universe_id' => $universe->id,
            'tick' => $tick,
            'religion_count' => count($spreadChanges),
        ]);
    }

    protected function spreadForReligion(Religion $religion, Collection $candidatePool, int $tick, int &$cursor): int
    {
        if ($cursor >= $candidatePool->count()) {
            return 0;
        }

        $remaining = $candidatePool->slice($cursor)->values();
        $target = max(1, (int) floor($remaining->count() * max(0.02, min(0.08, $religion->spread_rate))));
        $selected = $remaining->take($target);

        if ($selected->isEmpty()) {
            return 0;
        }

        $religion->actors()->syncWithoutDetaching(
            $selected->pluck('id')->mapWithKeys(
                fn (int $actorId) => [$actorId => ['believed_at_tick' => $tick]]
            )->all()
        );

        $cursor += $selected->count();

        return $selected->count();
    }
}
