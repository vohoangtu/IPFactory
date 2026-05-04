<?php

namespace App\Modules\Achievement\Services;

use App\Modules\Intelligence\Models\Actor;
use App\Modules\World\Models\Universe;
use App\Modules\Achievement\Entities\Achievement;
use App\Modules\Achievement\Entities\UniverseAchievement;
use App\Modules\Achievement\Entities\ActorAchievement;
use Illuminate\Support\Facades\Log;

class AchievementEngine
{
    /**
     * Evaluate achievements for a universe and auto-unlock matching ones.
     */
    public function evaluateForUniverse(Universe $universe): array
    {
        $unlocked = [];
        $state = $this->buildState($universe);

        /** @var Achievement $achievement */
        foreach (Achievement::all() as $achievement) {
            if ($universe->achievements()->where('achievement_id', $achievement->id)->exists()) {
                continue;
            }

            if ($this->matches($achievement->conditions ?? [], $state)) {
                $universe->achievements()->attach($achievement->id, ['unlocked_at' => now()]);
                $unlocked[] = $achievement->code;
            }
        }

        return $unlocked;
    }

    /**
     * Evaluate achievements for a specific actor.
     */
    public function evaluateForActor(Actor $actor, Universe $universe): array
    {
        $unlocked = [];
        $state = $this->buildActorState($actor, $universe);

        foreach (Achievement::where('category', 'celebrity')->orWhere('category', 'survival')->get() as $achievement) {
            if ($actor->achievements()->where('achievement_id', $achievement->id)->exists()) {
                continue;
            }

            if ($this->matches($achievement->conditions ?? [], $state)) {
                $actor->achievements()->attach($achievement->id, ['unlocked_at' => now()]);
                $unlocked[] = $achievement->code;
            }
        }

        return $unlocked;
    }

    protected function buildState(Universe $universe): array
    {
        $world = $universe->world;
        $tick = $universe->current_tick;

        return [
            'tick' => $tick,
            'entropy' => $universe->entropy ?? 0,
            'epoch' => $world?->current_epoch ?? 'unknown',
            'actor_count' => $universe->actors()->count(),
            'technology_count' => $universe->technologies()->count(),
            'event_count' => $universe->worldEvents()->count(),
            'scar_count' => $universe->actors()->whereNotNull('scars')->count(),
            'civilization_count' => $universe->civilizations()->count(),
        ];
    }

    protected function buildActorState(Actor $actor, Universe $universe): array
    {
        return [
            'tick' => $universe->current_tick,
            'hero_stage' => $actor->hero_stage ?? 'commoner',
            'trait_dominance' => $actor->traits['dominance'] ?? 0,
            'trait_empathy' => $actor->traits['empathy'] ?? 0,
            'scars_count' => is_array($actor->scars) ? count($actor->scars) : 0,
            'is_celebrity' => !empty($actor->celebrity_data),
        ];
    }

    protected function matches(array $conditions, array $state): bool
    {
        foreach ($conditions as $key => $expected) {
            $actual = $state[$key] ?? null;

            if (is_array($expected)) {
                $op = $expected['op'] ?? 'eq';
                $val = $expected['value'] ?? null;

                switch ($op) {
                    case 'eq':
                        if ($actual != $val) return false;
                        break;
                    case 'gte':
                        if (($actual ?? 0) < $val) return false;
                        break;
                    case 'lte':
                        if (($actual ?? 0) > $val) return false;
                        break;
                    case 'in':
                        if (!in_array($actual, (array) $val, true)) return false;
                        break;
                    case 'contains':
                        if (!is_array($actual) || !in_array($val, $actual, true)) return false;
                        break;
                }
            } else {
                if ($actual != $expected) return false;
            }
        }

        return true;
    }
}
